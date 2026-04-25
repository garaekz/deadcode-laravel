<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Console;

use Deadcode\Support\SupervisorBinaryResolver;
use Illuminate\Console\Command;
use Oxhq\Oxcribe\Support\DeadcoreBinaryResolver;
use Oxhq\Oxcribe\Support\PackageVersion;
use RuntimeException;
use Symfony\Component\Process\Process;

final class DoctorCommand extends Command
{
    protected $signature = 'deadcode:doctor {--project-root= : Override the Laravel app root to inspect}';

    protected $description = 'Run a local deadcode preflight for the target Laravel app and runtime binaries';

    public function handle(): int
    {
        $deadcodeConfig = (array) config('deadcode', []);
        $deadcoreConfig = (array) config('oxcribe.deadcore', []);
        $analysisConfig = (array) config('oxcribe.analysis.scan', []);
        $projectRoot = $this->resolveProjectRoot($deadcoreConfig);
        $workingDirectory = (string) ($deadcoreConfig['working_directory'] ?? $projectRoot);
        $binaryResolver = new DeadcoreBinaryResolver;

        $blocking = false;

        $this->components->twoColumnDetail('Package', PackageVersion::label());
        $this->newLine();

        if (is_dir($projectRoot)) {
            $this->report('PASS', 'Project root', $projectRoot);
        } else {
            $blocking = true;
            $this->report('FAIL', 'Project root', sprintf(
                'Directory "%s" does not exist. Re-run with --project-root=/absolute/path.',
                $projectRoot,
            ));
        }

        $composerJson = rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.json';
        if (is_file($composerJson)) {
            $this->report('PASS', 'composer.json', $composerJson);
        } else {
            $blocking = true;
            $this->report('FAIL', 'composer.json', 'Missing composer.json in the resolved project root.');
        }

        $targets = array_values(array_filter(
            (array) ($analysisConfig['targets'] ?? []),
            static fn (mixed $target): bool => is_string($target) && trim($target) !== '',
        ));
        if ($targets === []) {
            $this->report('WARN', 'Analysis targets', 'No scan targets are configured under oxcribe.analysis.scan.targets.');
        } else {
            $this->report('PASS', 'Analysis targets', implode(', ', $targets));
        }

        try {
            $supervisorBinary = (new SupervisorBinaryResolver)->resolve($deadcodeConfig, base_path());
            $this->report('PASS', 'Supervisor binary', $supervisorBinary);
        } catch (RuntimeException $exception) {
            $blocking = true;
            $this->report('FAIL', 'Supervisor binary', $exception->getMessage());
            $this->line('      Next: set DEADCODE_SUPERVISOR_BINARY to the deadcode-supervisor executable used by `deadcode:analyze`.');
        }

        try {
            $binary = $binaryResolver->resolve($deadcoreConfig, $workingDirectory);
            $this->report('PASS', 'Deadcore binary', $binary);

            $version = $this->resolveBinaryVersion($binary, $workingDirectory);
            if ($version !== null) {
                $this->report('PASS', 'Deadcore version', $version);
            } else {
                $this->report('WARN', 'Deadcore version', 'Binary is executable, but --version did not return cleanly.');
            }
        } catch (RuntimeException $exception) {
            $blocking = true;
            $suggestedPath = $binaryResolver->suggestedInstallPath($deadcoreConfig, $workingDirectory);
            $this->report('FAIL', 'Deadcore binary', $exception->getMessage());
            $sourceRoot = trim((string) ($deadcoreConfig['source_root'] ?? ''));
            $installCommand = sprintf('php artisan deadcode:install-binary %s', PackageVersion::TAG);
            if ($sourceRoot !== '') {
                $installCommand .= sprintf(' --source-root=%s', $sourceRoot);
            }
            $this->line(sprintf('      Next: run `%s` or point DEADCORE_BINARY to an executable path.', $installCommand));
            $this->line(sprintf('      Hint: the default app-local install path is %s', $suggestedPath));
        }

        $this->newLine();

        if (! $blocking) {
            $this->info('deadcode-laravel is ready for local analysis.');
            $this->line('Next: run `php artisan deadcode:analyze` or `php artisan deadcode:report`.');

            return self::SUCCESS;
        }

        $this->error('Local deadcode preflight found blocking issues.');
        $this->line('Next: fix the failed checks above, then rerun `php artisan deadcode:doctor`.');

        return self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $deadcoreConfig
     */
    private function resolveProjectRoot(array $deadcoreConfig): string
    {
        $fromOption = trim((string) $this->option('project-root'));
        if ($fromOption !== '') {
            return $fromOption;
        }

        $fromConfig = trim((string) ($deadcoreConfig['working_directory'] ?? ''));
        if ($fromConfig !== '') {
            return $fromConfig;
        }

        return base_path();
    }

    private function resolveBinaryVersion(string $binary, string $workingDirectory): ?string
    {
        $process = new Process([$binary, '--version'], $workingDirectory, null, null, 5);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());

        return $output !== '' ? $output : null;
    }

    private function report(string $status, string $label, string $message): void
    {
        $this->line(sprintf('%-5s %s: %s', $status, $label, $message));
    }
}

<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Oxhq\Oxcribe\Support\PackageVersion;
use RuntimeException;

final class InstallSupervisorCommand extends Command
{
    protected $signature = 'deadcode:install-supervisor
        {version? : Release version or tag to install}
        {--path= : Install destination relative to the app base path}
        {--os= : Override operating system (linux, darwin, windows)}
        {--arch= : Override CPU architecture (amd64, arm64)}
        {--force : Replace an existing binary at the destination}';

    protected $description = 'Download and install the matching deadcode-supervisor binary for this machine';

    public function handle(): int
    {
        try {
            $config = (array) config('deadcode', []);
            $release = (array) ($config['supervisor_release'] ?? []);
            $tag = $this->resolveTag($this->argument('version'), $release);
            $os = $this->resolveOperatingSystem($this->option('os'));
            $arch = $this->resolveArchitecture($this->option('arch'));
            $repository = trim((string) ($release['repository'] ?? ''));
            $baseUrl = rtrim(trim((string) ($release['base_url'] ?? 'https://github.com')), '/');
            $installPath = $this->resolveInstallPath(
                (string) ($this->option('path') ?: ($config['supervisor_install_path'] ?? 'bin/deadcode-supervisor')),
                $os,
            );

            if ($repository === '') {
                throw new RuntimeException('Missing deadcode.supervisor_release.repository / DEADCODE_SUPERVISOR_RELEASE_REPOSITORY.');
            }

            if (is_file($installPath) && ! $this->option('force')) {
                throw new RuntimeException(sprintf(
                    'A supervisor binary already exists at "%s". Re-run with --force to replace it.',
                    $installPath,
                ));
            }

            $asset = $this->assetName($tag, $os, $arch);
            $checksumsUrl = sprintf('%s/%s/releases/download/%s/checksums.txt', $baseUrl, $repository, $tag);
            $assetUrl = sprintf('%s/%s/releases/download/%s/%s', $baseUrl, $repository, $tag, $asset);

            $this->installFromRelease($tag, $os, $arch, $asset, $checksumsUrl, $assetUrl, $installPath);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $release
     */
    private function resolveTag(mixed $value, array $release): string
    {
        $resolved = is_string($value) && trim($value) !== ''
            ? trim($value)
            : trim((string) ($release['version'] ?? ''));

        if ($resolved === '') {
            $resolved = PackageVersion::TAG;
        }

        return str_starts_with($resolved, 'v') ? $resolved : 'v'.$resolved;
    }

    private function resolveOperatingSystem(mixed $value): string
    {
        $resolved = strtolower(trim((string) $value));

        if ($resolved !== '') {
            return match ($resolved) {
                'mac', 'macos', 'darwin' => 'darwin',
                'win', 'windows' => 'windows',
                'linux' => 'linux',
                default => throw new RuntimeException(sprintf('Unsupported operating system "%s".', $resolved)),
            };
        }

        return match (PHP_OS_FAMILY) {
            'Darwin' => 'darwin',
            'Windows' => 'windows',
            'Linux' => 'linux',
            default => throw new RuntimeException(sprintf('Unsupported operating system family "%s".', PHP_OS_FAMILY)),
        };
    }

    private function resolveArchitecture(mixed $value): string
    {
        $resolved = strtolower(trim((string) $value));

        if ($resolved === '') {
            $resolved = strtolower((string) php_uname('m'));
        }

        return match ($resolved) {
            'x86_64', 'amd64' => 'amd64',
            'arm64', 'aarch64' => 'arm64',
            default => throw new RuntimeException(sprintf('Unsupported architecture "%s".', $resolved)),
        };
    }

    private function resolveInstallPath(string $path, string $os): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            throw new RuntimeException('Supervisor install path is empty. Set DEADCODE_SUPERVISOR_INSTALL_PATH or pass --path.');
        }

        $resolved = $this->isAbsolutePath($trimmed)
            ? $trimmed
            : base_path($trimmed);

        if ($os === 'windows' && ! str_ends_with(strtolower($resolved), '.exe')) {
            return $resolved.'.exe';
        }

        return $resolved;
    }

    private function assetName(string $tag, string $os, string $arch): string
    {
        return sprintf(
            'deadcode-supervisor_%s_%s_%s%s',
            $tag,
            $os,
            $arch,
            $os === 'windows' ? '.exe' : '',
        );
    }

    private function installFromRelease(
        string $tag,
        string $os,
        string $arch,
        string $asset,
        string $checksumsUrl,
        string $assetUrl,
        string $installPath,
    ): void {
        $this->line(sprintf('Downloading supervisor %s for %s/%s...', $tag, $os, $arch));

        $checksumsResponse = Http::accept('text/plain')
            ->timeout(30)
            ->get($checksumsUrl);
        if ($checksumsResponse->failed()) {
            throw new RuntimeException(sprintf(
                'Unable to download supervisor release checksums from %s (status %d).',
                $checksumsUrl,
                $checksumsResponse->status(),
            ));
        }

        $expectedChecksum = $this->resolveChecksum($checksumsResponse->body(), $asset);
        $binaryResponse = Http::timeout(120)->get($assetUrl);
        if ($binaryResponse->failed()) {
            throw new RuntimeException(sprintf(
                'Unable to download %s from %s (status %d).',
                $asset,
                $assetUrl,
                $binaryResponse->status(),
            ));
        }

        $binaryContents = $binaryResponse->body();
        $actualChecksum = hash('sha256', $binaryContents);

        if (! hash_equals($expectedChecksum, $actualChecksum)) {
            throw new RuntimeException(sprintf(
                'Checksum verification failed for %s. Expected %s, received %s.',
                $asset,
                $expectedChecksum,
                $actualChecksum,
            ));
        }

        File::ensureDirectoryExists(dirname($installPath));
        File::put($installPath, $binaryContents);

        if ($os !== 'windows') {
            @chmod($installPath, 0755);
        }

        $this->info(sprintf('Installed deadcode-supervisor %s to %s', $tag, $installPath));
    }

    private function resolveChecksum(string $body, string $asset): string
    {
        foreach (preg_split("/(\r?\n)/", trim($body)) ?: [] as $line) {
            if (! preg_match('/^([a-f0-9]{64})\s+\*?(.+)$/i', trim($line), $matches)) {
                continue;
            }

            if (trim($matches[2]) === $asset) {
                return strtolower($matches[1]);
            }
        }

        throw new RuntimeException(sprintf(
            'Unable to find checksum entry for %s in checksums.txt.',
            $asset,
        ));
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}

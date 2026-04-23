<?php

declare(strict_types=1);

namespace Deadcode\Console\Commands;

use Deadcode\Runtime\Runtime;
use Deadcode\Tasks\AnalyzeProjectTask;
use Illuminate\Console\Command;
use Throwable;

final class DeadcodeAnalyzeCommand extends Command
{
    protected $signature = 'deadcode:analyze {projectPath?}';

    protected $description = 'Analyze a Laravel project for dead code candidates.';

    public function handle(Runtime $runtime): int
    {
        try {
            $result = $runtime->run(
                new AnalyzeProjectTask($this->argument('projectPath') ?? base_path()),
                function (array $frame): void {
                    if (($frame['type'] ?? null) === 'task.progress') {
                        $this->line((string) $frame['message']);
                    }
                },
            );

            $this->components->info('Findings: '.$result->data['findingCount']);
            $this->components->info('Report: '.$result->data['reportPath']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}

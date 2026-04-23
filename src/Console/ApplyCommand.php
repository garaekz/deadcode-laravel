<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Console;

use Illuminate\Console\Command;

final class ApplyCommand extends Command
{
    use WritesJsonOutput;

    protected $signature = 'deadcode:apply {--report= : Optional deadcode report file to reference} {--write=} {--pretty}';

    protected $description = 'Emit the current local apply status for deadcode-laravel';

    public function handle(): int
    {
        $report = trim((string) $this->option('report'));
        $this->warn('Automatic dead code remediation is not implemented in this reset package surface.');

        return $this->writeJsonPayload(
            [
                'contractVersion' => 'deadcode.apply.v1',
                'status' => 'noop',
                'report' => $report !== '' ? $report : null,
                'changesApplied' => 0,
                'message' => 'No edits were applied. This command is a local-only placeholder in the reset package surface.',
            ],
            (string) $this->option('write'),
            (bool) $this->option('pretty'),
        );
    }
}

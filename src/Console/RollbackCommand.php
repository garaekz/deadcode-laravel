<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Console;

use Illuminate\Console\Command;

final class RollbackCommand extends Command
{
    use WritesJsonOutput;

    protected $signature = 'deadcode:rollback {--write=} {--pretty}';

    protected $description = 'Emit the current local rollback status for deadcode-laravel';

    public function handle(): int
    {
        $this->warn('Rollback is a no-op until apply semantics are implemented.');

        return $this->writeJsonPayload(
            [
                'contractVersion' => 'deadcode.rollback.v1',
                'status' => 'noop',
                'changesRolledBack' => 0,
                'message' => 'No changes were rolled back. This command is a local-only placeholder in the reset package surface.',
            ],
            (string) $this->option('write'),
            (bool) $this->option('pretty'),
        );
    }
}

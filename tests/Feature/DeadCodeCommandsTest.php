<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Deadcode\Console\Commands\DeadcodeAnalyzeCommand;

it('keeps deadcode analyze owned by the runtime-backed analyze command', function (): void {
    $command = Artisan::all()['deadcode:analyze'];

    expect($command)->toBeInstanceOf(DeadcodeAnalyzeCommand::class)
        ->and($command->getDescription())->toBe('Analyze a Laravel project for dead code candidates.')
        ->and($command->getDefinition()->hasArgument('projectPath'))->toBeTrue()
        ->and($command->getDefinition()->hasOption('write'))->toBeFalse()
        ->and($command->getDefinition()->hasOption('pretty'))->toBeFalse();
});

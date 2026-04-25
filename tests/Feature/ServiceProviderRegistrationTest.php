<?php

declare(strict_types=1);

use Deadcode\DeadcodeServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Oxhq\Oxcribe\OxcribeServiceProvider;

it('registers the package service provider through the public Deadcode identity', function () {
    if (! class_exists(DeadcodeServiceProvider::class)) {
        $this->markTestSkipped('DeadcodeServiceProvider has not been created yet.');
    }

    expect(app()->getProvider(DeadcodeServiceProvider::class))
        ->toBeInstanceOf(DeadcodeServiceProvider::class);
});

it('keeps the legacy Oxcribe service provider available for compatibility', function () {
    expect(class_exists(OxcribeServiceProvider::class))->toBeTrue()
        ->and(is_subclass_of(DeadcodeServiceProvider::class, OxcribeServiceProvider::class))->toBeTrue();
});

it('registers the local deadcode command surface', function () {
    $commands = array_keys(Artisan::all());

    expect($commands)
        ->toContain(
            'deadcode:doctor',
            'deadcode:install-binary',
            'deadcode:install-supervisor',
            'deadcode:analyze',
            'deadcode:report',
            'deadcode:apply',
            'deadcode:rollback',
        )
        ->not->toContain(
            'oxcribe:doctor',
            'oxcribe:install-binary',
            'oxcribe:analyze',
            'oxcribe:export-openapi',
            'oxcribe:publish',
        );
});

it('keeps the deadcode command surface honest about unsupported project switching', function () {
    $analyze = Artisan::all()['deadcode:analyze'];
    $report = Artisan::all()['deadcode:report'];

    expect($analyze->getDefinition()->hasOption('project-root'))->toBeFalse()
        ->and($report->getDefinition()->hasOption('project-root'))->toBeFalse()
        ->and($analyze->getDefinition()->hasArgument('projectPath'))->toBeTrue()
        ->and($analyze->getDefinition()->hasOption('write'))->toBeFalse()
        ->and($analyze->getDefinition()->hasOption('pretty'))->toBeFalse()
        ->and($report->getDefinition()->hasOption('write'))->toBeTrue()
        ->and($report->getDefinition()->hasOption('input'))->toBeTrue()
        ->and($report->getDefinition()->hasOption('format'))->toBeTrue();
});

it('does not register obsolete visibility middleware aliases', function () {
    $aliases = app('router')->getMiddleware();

    expect(config('oxcribe.visibility'))->toBeNull()
        ->and($aliases)->not->toHaveKeys([
            'oxcribe.publish',
            'ox.publish',
            'oxcribe.private',
            'ox.private',
        ]);
});

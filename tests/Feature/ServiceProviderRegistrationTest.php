<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Oxhq\Oxcribe\OxcribeServiceProvider;

it('registers the package service provider in the test application', function () {
    if (! class_exists(OxcribeServiceProvider::class)) {
        $this->markTestSkipped('OxcribeServiceProvider has not been created yet.');
    }

    expect(app()->getProvider(OxcribeServiceProvider::class))
        ->toBeInstanceOf(OxcribeServiceProvider::class);
});

it('registers the local deadcode command surface', function () {
    $commands = array_keys(Artisan::all());

    expect($commands)
        ->toContain(
            'deadcode:doctor',
            'deadcode:install-binary',
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

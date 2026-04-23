<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Oxhq\Oxcribe\Support\PackageVersion;

it('reports a healthy local preflight when the project and deadcore binary are ready', function () {
    $projectRoot = sys_get_temp_dir().'/deadcode-doctor-project-'.bin2hex(random_bytes(6));
    $binaryPath = makePortablePhpCommand(
        $projectRoot.'/bin',
        'deadcore',
        <<<'PHP'
fwrite(STDOUT, "deadcore version 0.1.1\n");
PHP
    );

    File::put($projectRoot.'/composer.json', json_encode(['name' => 'acme/example'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    config()->set('oxcribe.deadcore.binary', $binaryPath);
    config()->set('oxcribe.analysis.scan.targets', ['app', 'routes']);

    $this->artisan('deadcode:doctor', ['--project-root' => $projectRoot])
        ->expectsOutputToContain('PASS  Project root:')
        ->expectsOutputToContain('PASS  composer.json:')
        ->expectsOutputToContain('PASS  Analysis targets: app, routes')
        ->expectsOutputToContain('PASS  Deadcore binary:')
        ->expectsOutputToContain('PASS  Deadcore version: deadcore version 0.1.1')
        ->expectsOutputToContain('deadcode-laravel is ready for local analysis.')
        ->assertSuccessful();

    File::deleteDirectory($projectRoot);
});

it('fails with actionable output when local preflight is missing a deadcore binary', function () {
    $projectRoot = sys_get_temp_dir().'/deadcode-doctor-broken-'.bin2hex(random_bytes(6));

    File::ensureDirectoryExists($projectRoot);
    File::put($projectRoot.'/composer.json', json_encode(['name' => 'acme/broken'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    config()->set('oxcribe.deadcore.binary', $projectRoot.'/bin/missing-deadcore');
    config()->set('oxcribe.deadcore.source_root', null);

    $this->artisan('deadcode:doctor', ['--project-root' => $projectRoot])
        ->expectsOutputToContain('FAIL  Deadcore binary:')
        ->expectsOutputToContain(sprintf('Next: run `php artisan deadcode:install-binary %s`', PackageVersion::TAG))
        ->expectsOutputToContain('Local deadcode preflight found blocking issues.')
        ->assertFailed();

    File::deleteDirectory($projectRoot);
});

it('suggests the configured local source root when deadcore is missing', function () {
    $projectRoot = sys_get_temp_dir().'/deadcode-doctor-source-'.bin2hex(random_bytes(6));

    File::ensureDirectoryExists($projectRoot);
    File::put($projectRoot.'/composer.json', json_encode(['name' => 'acme/source'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    config()->set('oxcribe.deadcore.binary', $projectRoot.'/bin/missing-deadcore');
    config()->set('oxcribe.deadcore.source_root', '/tmp/deadcore-source');

    $this->artisan('deadcode:doctor', ['--project-root' => $projectRoot])
        ->expectsOutputToContain(sprintf(
            'php artisan deadcode:install-binary %s --source-root=/tmp/deadcore-source',
            PackageVersion::TAG,
        ))
        ->assertFailed();

    File::deleteDirectory($projectRoot);
});

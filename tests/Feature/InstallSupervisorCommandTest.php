<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Oxhq\Oxcribe\Support\PackageVersion;

it('downloads and installs the matching supervisor binary', function () {
    $directory = sys_get_temp_dir().'/deadcode-supervisor-install-'.bin2hex(random_bytes(6));
    $binaryPath = $directory.'/bin/deadcode-supervisor';
    $contents = "fake-supervisor-binary\n";
    $checksum = hash('sha256', $contents);
    $tag = PackageVersion::TAG;

    config()->set('deadcode.supervisor_release.repository', 'deadcode/go-supervisor');
    config()->set('deadcode.supervisor_release.base_url', 'https://github.com');

    Http::fake([
        "https://github.com/deadcode/go-supervisor/releases/download/{$tag}/checksums.txt" => Http::response(
            "{$checksum}  deadcode-supervisor_{$tag}_linux_amd64\n",
        ),
        "https://github.com/deadcode/go-supervisor/releases/download/{$tag}/deadcode-supervisor_{$tag}_linux_amd64" => Http::response(
            $contents,
            200,
            ['Content-Type' => 'application/octet-stream'],
        ),
    ]);

    $this->artisan('deadcode:install-supervisor', [
        'version' => $tag,
        '--path' => $binaryPath,
        '--os' => 'linux',
        '--arch' => 'amd64',
    ])
        ->expectsOutput(sprintf('Downloading supervisor %s for linux/amd64...', $tag))
        ->expectsOutput(sprintf('Installed deadcode-supervisor %s to %s', $tag, $binaryPath))
        ->assertSuccessful();

    expect(File::exists($binaryPath))->toBeTrue()
        ->and(File::get($binaryPath))->toBe($contents);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'checksums.txt'));
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), "deadcode-supervisor_{$tag}_linux_amd64"));

    File::deleteDirectory($directory);
});

it('appends the windows executable suffix for supervisor installs', function () {
    $directory = sys_get_temp_dir().'/deadcode-supervisor-install-'.bin2hex(random_bytes(6));
    $binaryPath = $directory.'/bin/deadcode-supervisor';
    $contents = "windows-supervisor-binary\r\n";
    $checksum = hash('sha256', $contents);
    $tag = PackageVersion::TAG;

    config()->set('deadcode.supervisor_release.repository', 'deadcode/go-supervisor');
    config()->set('deadcode.supervisor_release.base_url', 'https://github.com');

    Http::fake([
        "https://github.com/deadcode/go-supervisor/releases/download/{$tag}/checksums.txt" => Http::response(
            "{$checksum}  deadcode-supervisor_{$tag}_windows_amd64.exe\n",
        ),
        "https://github.com/deadcode/go-supervisor/releases/download/{$tag}/deadcode-supervisor_{$tag}_windows_amd64.exe" => Http::response(
            $contents,
            200,
            ['Content-Type' => 'application/octet-stream'],
        ),
    ]);

    $this->artisan('deadcode:install-supervisor', [
        'version' => $tag,
        '--path' => $binaryPath,
        '--os' => 'windows',
        '--arch' => 'amd64',
    ])->assertSuccessful();

    expect(File::exists($binaryPath.'.exe'))->toBeTrue();

    File::deleteDirectory($directory);
});

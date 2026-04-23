<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Oxhq\Oxcribe\Support\PackageVersion;

it('downloads and installs the matching deadcore binary', function () {
    $directory = sys_get_temp_dir().'/deadcode-install-'.bin2hex(random_bytes(6));
    $binaryPath = $directory.'/bin/deadcore';
    $contents = "fake-deadcore-binary\n";
    $checksum = hash('sha256', $contents);
    $tag = PackageVersion::TAG;

    config()->set('oxcribe.deadcore.release.repository', 'deadcode/deadcore');
    config()->set('oxcribe.deadcore.release.base_url', 'https://github.com');

    Http::fake([
        "https://github.com/deadcode/deadcore/releases/download/{$tag}/checksums.txt" => Http::response(
            "{$checksum}  deadcore_{$tag}_linux_amd64\n",
        ),
        "https://github.com/deadcode/deadcore/releases/download/{$tag}/deadcore_{$tag}_linux_amd64" => Http::response(
            $contents,
            200,
            ['Content-Type' => 'application/octet-stream'],
        ),
    ]);

    $this->artisan('deadcode:install-binary', [
        'version' => $tag,
        '--path' => $binaryPath,
        '--os' => 'linux',
        '--arch' => 'amd64',
    ])
        ->expectsOutput(sprintf('Downloading %s for linux/amd64...', $tag))
        ->expectsOutput(sprintf('Installed deadcore %s to %s', $tag, $binaryPath))
        ->assertSuccessful();

    expect(File::exists($binaryPath))->toBeTrue()
        ->and(File::get($binaryPath))->toBe($contents);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'checksums.txt'));
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), "deadcore_{$tag}_linux_amd64"));

    File::deleteDirectory($directory);
});

it('fails when the release checksum does not match the downloaded binary', function () {
    $directory = sys_get_temp_dir().'/deadcode-install-'.bin2hex(random_bytes(6));
    $binaryPath = $directory.'/bin/deadcore';
    $tag = PackageVersion::TAG;

    config()->set('oxcribe.deadcore.release.repository', 'deadcode/deadcore');
    config()->set('oxcribe.deadcore.release.base_url', 'https://github.com');
    config()->set('oxcribe.deadcore.source_root', null);

    Http::fake([
        "https://github.com/deadcode/deadcore/releases/download/{$tag}/checksums.txt" => Http::response(
            str_repeat('a', 64)."  deadcore_{$tag}_linux_amd64\n",
        ),
        "https://github.com/deadcode/deadcore/releases/download/{$tag}/deadcore_{$tag}_linux_amd64" => Http::response(
            "wrong-binary\n",
            200,
            ['Content-Type' => 'application/octet-stream'],
        ),
    ]);

    $this->artisan('deadcode:install-binary', [
        'version' => $tag,
        '--path' => $binaryPath,
        '--os' => 'linux',
        '--arch' => 'amd64',
    ])
        ->expectsOutputToContain('Checksum verification failed')
        ->assertFailed();

    expect(File::exists($binaryPath))->toBeFalse();
});

it('appends the windows executable suffix when needed', function () {
    $directory = sys_get_temp_dir().'/deadcode-install-'.bin2hex(random_bytes(6));
    $binaryPath = $directory.'/bin/deadcore';
    $contents = "windows-binary\r\n";
    $checksum = hash('sha256', $contents);
    $tag = PackageVersion::TAG;

    config()->set('oxcribe.deadcore.release.repository', 'deadcode/deadcore');
    config()->set('oxcribe.deadcore.release.base_url', 'https://github.com');

    Http::fake([
        "https://github.com/deadcode/deadcore/releases/download/{$tag}/checksums.txt" => Http::response(
            "{$checksum}  deadcore_{$tag}_windows_amd64.exe\n",
        ),
        "https://github.com/deadcode/deadcore/releases/download/{$tag}/deadcore_{$tag}_windows_amd64.exe" => Http::response(
            $contents,
            200,
            ['Content-Type' => 'application/octet-stream'],
        ),
    ]);

    $this->artisan('deadcode:install-binary', [
        'version' => $tag,
        '--path' => $binaryPath,
        '--os' => 'windows',
        '--arch' => 'amd64',
    ])->assertSuccessful();

    expect(File::exists($binaryPath.'.exe'))->toBeTrue();

    File::deleteDirectory($directory);
});

it('falls back to a local deadcore source checkout when release checksums are unavailable', function () {
    $directory = sys_get_temp_dir().'/deadcode-install-'.bin2hex(random_bytes(6));
    $binaryPath = $directory.'/bin/deadcore';
    $sourceRoot = $directory.'/deadcore-source';
    $tag = PackageVersion::TAG;

    makeMinimalRustDeadcoreSource($sourceRoot, "source-built-binary\n");

    config()->set('oxcribe.deadcore.release.repository', 'deadcode/deadcore');
    config()->set('oxcribe.deadcore.release.base_url', 'https://github.com');
    config()->set('oxcribe.deadcore.source_root', $sourceRoot);

    Http::fake([
        "https://github.com/deadcode/deadcore/releases/download/{$tag}/checksums.txt" => Http::response('', 404),
    ]);

    $this->artisan('deadcode:install-binary', [
        'version' => $tag,
        '--path' => $binaryPath,
        '--os' => 'linux',
        '--arch' => 'amd64',
    ])
        ->expectsOutput(sprintf('Downloading %s for linux/amd64...', $tag))
        ->expectsOutputToContain('Unable to download release checksums')
        ->expectsOutputToContain('Falling back to local deadcore source')
        ->expectsOutput(sprintf('Installed deadcore from source to %s', $binaryPath))
        ->assertSuccessful();

    expect(File::exists($binaryPath))->toBeTrue()
        ->and(File::size($binaryPath))->toBeGreaterThan(0);

    File::deleteDirectory($directory);
});

it('prefers a local deadcore source checkout without hitting the network when requested', function () {
    $directory = sys_get_temp_dir().'/deadcode-install-'.bin2hex(random_bytes(6));
    $binaryPath = $directory.'/bin/deadcore';
    $sourceRoot = $directory.'/deadcore-source';

    makeMinimalRustDeadcoreSource($sourceRoot, "source-preferred-binary\n");

    config()->set('oxcribe.deadcore.release.repository', 'deadcode/deadcore');
    config()->set('oxcribe.deadcore.release.base_url', 'https://github.com');

    Http::fake();

    $this->artisan('deadcode:install-binary', [
        'version' => PackageVersion::TAG,
        '--path' => $binaryPath,
        '--os' => 'linux',
        '--arch' => 'amd64',
        '--source-root' => $sourceRoot,
        '--prefer-source' => true,
    ])
        ->expectsOutputToContain('Building deadcore from source')
        ->expectsOutput(sprintf('Installed deadcore from source to %s', $binaryPath))
        ->assertSuccessful();

    expect(File::exists($binaryPath))->toBeTrue()
        ->and(File::size($binaryPath))->toBeGreaterThan(0);

    Http::assertNothingSent();

    File::deleteDirectory($directory);
});

function makeMinimalRustDeadcoreSource(string $sourceRoot, string $output): void
{
    File::ensureDirectoryExists($sourceRoot.'/src');
    File::put($sourceRoot.'/Cargo.toml', <<<'TOML'
[package]
name = "deadcore"
version = "0.1.3"
edition = "2021"
TOML);
    File::put(
        $sourceRoot.'/src/main.rs',
        sprintf("fn main() {\n    print!(%s);\n}\n", json_encode($output, JSON_THROW_ON_ERROR))
    );
}

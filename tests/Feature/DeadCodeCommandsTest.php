<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Oxhq\Oxcribe\Contracts\RuntimeSnapshotFactory;
use Oxhq\Oxcribe\Data\AppSnapshot;
use Oxhq\Oxcribe\Data\RuntimeSnapshot;

it('keeps deadcode analyze as the raw deadcore response emitter', function () {
    [$projectRoot] = configureFakeDeadcoreCommand(deadcoreControllerReachabilityPayload());
    bindFakeRuntimeSnapshotFactory($projectRoot);

    expect(Artisan::call('deadcode:analyze'))->toBe(0);

    $payload = json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toBe(deadcoreControllerReachabilityPayload())
        ->and($payload)->not->toHaveKeys(['projectRoot', 'summary']);

    File::deleteDirectory($projectRoot);
});

it('emits controller reachability findings in the deadcode report payload', function () {
    [$projectRoot] = configureFakeDeadcoreCommand(deadcoreControllerReachabilityPayload());
    bindFakeRuntimeSnapshotFactory($projectRoot);

    expect(Artisan::call('deadcode:report'))->toBe(0);

    $payload = json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toMatchArray([
        'contractVersion' => 'deadcode.report.v1',
        'projectRoot' => $projectRoot,
        'requestId' => 'req-controller-reachability',
        'status' => 'ok',
        'meta' => [
            'durationMs' => 17,
            'cacheHits' => 2,
            'cacheMisses' => 1,
        ],
        'summary' => [
            'entrypointCount' => 1,
            'symbolCount' => 2,
            'reachableSymbolCount' => 1,
            'unreachableSymbolCount' => 1,
            'findingCount' => 1,
            'removalChangeCount' => 1,
        ],
        'entrypoints' => [
            [
                'kind' => 'runtime_route',
                'symbol' => 'App\\Http\\Controllers\\UserController::index',
                'source' => 'users.index',
            ],
        ],
        'findings' => [
            [
                'symbol' => 'App\\Http\\Controllers\\UserController::unused',
                'category' => 'unused_controller_method',
                'confidence' => 'high',
                'file' => 'app/Http/Controllers/UserController.php',
                'startLine' => 20,
                'endLine' => 24,
            ],
        ],
        'removalPlan' => [
            'changeSets' => [
                [
                    'file' => 'app/Http/Controllers/UserController.php',
                    'symbol' => 'App\\Http\\Controllers\\UserController::unused',
                    'startLine' => 20,
                    'endLine' => 24,
                ],
            ],
        ],
    ])->and($payload)->not->toHaveKeys(['routeMatches', 'diagnostics']);

    expect($payload['symbols'][0])->toMatchArray([
        'symbol' => 'App\\Http\\Controllers\\UserController::index',
        'reachableFromRuntime' => true,
    ]);

    expect($payload['symbols'][1])->toMatchArray([
        'symbol' => 'App\\Http\\Controllers\\UserController::unused',
        'reachableFromRuntime' => false,
    ]);

    File::deleteDirectory($projectRoot);
});

function configureFakeDeadcoreCommand(array $payload): array
{
    $projectRoot = sys_get_temp_dir().'/deadcode-task7-'.bin2hex(random_bytes(6));
    $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $script = sprintf(
        <<<'PHP'
$payload = %s;
fwrite(STDOUT, $payload);
PHP,
        var_export($encodedPayload, true),
    );

    $binaryPath = makePortablePhpCommand($projectRoot.'/bin', 'deadcore', $script);

    config()->set('oxcribe.deadcore.binary', $binaryPath);

    return [$projectRoot, $binaryPath];
}

function bindFakeRuntimeSnapshotFactory(string $projectRoot): void
{
    app()->instance(RuntimeSnapshotFactory::class, new class($projectRoot) implements RuntimeSnapshotFactory
    {
        public function __construct(
            private readonly string $projectRoot,
        ) {}

        public function make(): RuntimeSnapshot
        {
            return new RuntimeSnapshot(
                app: new AppSnapshot(
                    basePath: $this->projectRoot,
                    laravelVersion: '12.0.0',
                    phpVersion: PHP_VERSION,
                    appEnv: 'testing',
                ),
                routes: [],
            );
        }
    });
}

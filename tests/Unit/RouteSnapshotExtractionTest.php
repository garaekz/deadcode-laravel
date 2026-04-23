<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Oxhq\Oxcribe\Contracts\RuntimeSnapshotFactory;
use Oxhq\Oxcribe\Support\RouteSnapshotExtractor;

if (! class_exists(OxcribeHardeningUser::class)) {
    class OxcribeHardeningUser extends Model
    {
        protected $guarded = [];
    }
}

if (! class_exists(OxcribeHardeningPost::class)) {
    class OxcribeHardeningPost extends Model
    {
        protected $guarded = [];
    }
}

if (! class_exists(OxcribeHardeningController::class)) {
    class OxcribeHardeningController
    {
        public function show(OxcribeHardeningUser $user, OxcribeHardeningPost $post): string
        {
            return 'ok';
        }
    }
}

if (! class_exists(OxcribeInvokableHardeningController::class)) {
    class OxcribeInvokableHardeningController
    {
        public function __invoke(): string
        {
            return 'ok';
        }
    }
}

it('extracts a route snapshot with a stable shape', function () {
    if (! class_exists(RouteSnapshotExtractor::class)) {
        $this->markTestSkipped('RouteSnapshotExtractor has not been created yet.');
    }

    Route::get('/oxcribe/snapshots/{snapshot}', static fn () => 'ok')
        ->name('oxcribe.snapshots.show')
        ->middleware(['web', 'throttle:60,1']);

    Route::getRoutes()->refreshNameLookups();
    $route = Route::getRoutes()->getByName('oxcribe.snapshots.show');

    expect($route)->not->toBeNull();

    $snapshot = app(RouteSnapshotExtractor::class)->extract($route);

    expect($snapshot)->toBeArray()
        ->and($snapshot)->toMatchArray([
            'name' => 'oxcribe.snapshots.show',
            'uri' => 'oxcribe/snapshots/{snapshot}',
        ])
        ->and($snapshot['methods'])->toBeArray()
        ->and($snapshot['action'])->toBeArray()
        ->and($snapshot['middleware'])->toBeArray();
});

it('captures path bindings and grouped prefixes for downstream route filtering', function () {
    Route::prefix('internal')->group(function (): void {
        Route::get('/oxcribe/users/{user}/posts/{post}', [OxcribeHardeningController::class, 'show'])
            ->name('oxcribe.internal.posts.show')
            ->middleware(['auth:sanctum']);
    });

    Route::getRoutes()->refreshNameLookups();
    $route = Route::getRoutes()->getByName('oxcribe.internal.posts.show');

    expect($route)->not->toBeNull();

    $snapshot = app(RouteSnapshotExtractor::class)->extract($route);

    expect($snapshot['prefix'])->toBe('internal')
        ->and($snapshot['uri'])->toBe('internal/oxcribe/users/{user}/posts/{post}')
        ->and($snapshot['bindings'])->toHaveCount(2)
        ->and($snapshot['bindings'][0])->toMatchArray([
            'parameter' => 'user',
            'kind' => 'implicit_model',
            'targetFqcn' => OxcribeHardeningUser::class,
            'isImplicit' => true,
        ])
        ->and($snapshot['bindings'][1])->toMatchArray([
            'parameter' => 'post',
            'kind' => 'implicit_model',
            'targetFqcn' => OxcribeHardeningPost::class,
            'isImplicit' => true,
        ]);
});

it('captures controller entrypoints from the runtime route table', function () {
    Route::get('/oxcribe/runtime/controller/{user}/{post}', [OxcribeHardeningController::class, 'show'])
        ->name('oxcribe.runtime.controller');

    Route::get('/oxcribe/runtime/invokable', OxcribeInvokableHardeningController::class)
        ->name('oxcribe.runtime.invokable');

    $runtime = app(RuntimeSnapshotFactory::class)->make()->toArray();

    $controllerRoute = collect($runtime['routes'])->firstWhere('name', 'oxcribe.runtime.controller');
    $invokableRoute = collect($runtime['routes'])->firstWhere('name', 'oxcribe.runtime.invokable');

    expect($controllerRoute)->not->toBeNull()
        ->and($controllerRoute['action'])->toMatchArray([
            'kind' => 'controller_method',
            'controller' => OxcribeHardeningController::class.'@show',
            'controllerClass' => OxcribeHardeningController::class,
            'controllerMethod' => 'show',
        ])
        ->and($invokableRoute)->not->toBeNull()
        ->and($invokableRoute['action'])->toMatchArray([
            'kind' => 'invokable_controller',
            'controller' => OxcribeInvokableHardeningController::class.'@__invoke',
            'controllerClass' => OxcribeInvokableHardeningController::class,
            'controllerMethod' => '__invoke',
        ]);
});

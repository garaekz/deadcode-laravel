<?php

declare(strict_types=1);

use Deadcode\Runtime\AppSnapshot;
use Deadcode\Runtime\RouteAction;
use Deadcode\Runtime\RouteBinding;
use Deadcode\Runtime\RouteSnapshot;
use Deadcode\Runtime\RuntimeSnapshot;

it('serializes runtime contracts into the deadcode wire shape', function (): void {
    $snapshot = new RuntimeSnapshot(
        app: new AppSnapshot(
            basePath: 'C:/work/app',
            laravelVersion: '12.0.0',
            phpVersion: '8.4.0',
            appEnv: 'testing',
        ),
        routes: [
            new RouteSnapshot(
                routeId: 'users.index',
                methods: ['GET'],
                uri: 'users',
                domain: null,
                name: 'users.index',
                prefix: null,
                middleware: ['web'],
                where: ['team' => '[0-9]+'],
                defaults: ['locale' => 'en'],
                bindings: [
                    new RouteBinding(
                        parameter: 'user',
                        kind: 'implicit',
                        targetFqcn: 'App\\Models\\User',
                        isImplicit: true,
                    ),
                ],
                action: new RouteAction(
                    kind: 'controller_method',
                    fqcn: 'App\\Http\\Controllers\\UserController',
                    method: 'index',
                ),
            ),
        ],
    );

    expect($snapshot->toArray())->toMatchArray([
        'app' => [
            'basePath' => 'C:/work/app',
            'laravelVersion' => '12.0.0',
            'phpVersion' => '8.4.0',
            'appEnv' => 'testing',
        ],
        'routes' => [
            [
                'routeId' => 'users.index',
                'methods' => ['GET'],
                'uri' => 'users',
                'domain' => null,
                'name' => 'users.index',
                'prefix' => null,
                'middleware' => ['web'],
                'where' => ['team' => '[0-9]+'],
                'defaults' => ['locale' => 'en'],
                'bindings' => [
                    [
                        'parameter' => 'user',
                        'kind' => 'implicit',
                        'targetFqcn' => 'App\\Models\\User',
                        'isImplicit' => true,
                    ],
                ],
                'action' => [
                    'kind' => 'controller_method',
                    'fqcn' => 'App\\Http\\Controllers\\UserController',
                    'method' => 'index',
                ],
            ],
        ],
    ]);
});

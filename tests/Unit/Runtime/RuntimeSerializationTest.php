<?php

declare(strict_types=1);

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\TaskResult;

it('serializes the runtime task contract baseline', function (): void {
    $task = new class implements Task
    {
        public function name(): string
        {
            return 'runtime.serialize';
        }

        public function payload(): array
        {
            return [
                'basePath' => 'C:/work/app',
                'include' => ['routes', 'commands'],
            ];
        }
    };

    expect($task->name())->toBe('runtime.serialize')
        ->and($task->payload())->toBe([
            'basePath' => 'C:/work/app',
            'include' => ['routes', 'commands'],
        ]);
});

it('serializes the runtime task result baseline', function (): void {
    $result = new TaskResult(
        status: 'ok',
        data: [
            'routes' => [
                [
                    'method' => 'GET',
                    'uri' => 'users',
                ],
            ],
        ],
        meta: [
            'durationMs' => 12,
            'source' => 'laravel',
        ],
    );

    expect($result->status)->toBe('ok')
        ->and($result->data)->toBe([
            'routes' => [
                [
                    'method' => 'GET',
                    'uri' => 'users',
                ],
            ],
        ])
        ->and($result->meta)->toBe([
            'durationMs' => 12,
            'source' => 'laravel',
        ]);
});

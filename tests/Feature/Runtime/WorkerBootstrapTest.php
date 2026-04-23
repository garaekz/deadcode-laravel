<?php

declare(strict_types=1);

use Deadcode\Runtime\Protocol\FrameCodec;
use Symfony\Component\Process\Process;

it('processes a fixture task through the worker bootstrap', function (): void {
    $packageRoot = dirname(__DIR__, 3);

    $process = new Process([
        PHP_BINARY,
        $packageRoot.'/bin/ox-runtime-worker.php',
        '--once',
    ], $packageRoot);

    $process->setInput(FrameCodec::encode([
        'type' => 'task.run',
        'taskClass' => 'Tests\\Fixtures\\Runtime\\FixtureTask',
        'payload' => ['name' => 'demo'],
    ]));

    $process->mustRun();

    $frame = FrameCodec::decode($process->getOutput());

    expect($frame['type'])->toBe('task.completed');
    expect($frame['result']['status'])->toBe('ok');
    expect($frame['result']['data'])->toBe([
        'message' => 'hello demo',
    ]);
    expect($frame['result']['events'])->toContain([
        'type' => 'task.progress',
        'taskId' => 'task-1',
        'message' => 'hello demo',
        'percent' => 100,
        'meta' => [],
    ]);
});

<?php

declare(strict_types=1);

use Deadcode\Runtime\Protocol\FrameCodec;
use Deadcode\Runtime\Worker\WorkerBootstrap;
use Symfony\Component\Process\Process;

it('processes a fixture task through the worker bootstrap', function (): void {
    $packageRoot = dirname(__DIR__, 3);
    $bootstrapPath = $packageRoot.'/tests/Fixtures/Runtime/fixture-bootstrap-app.php';

    $process = new Process([
        PHP_BINARY,
        $packageRoot.'/bin/ox-runtime-worker.php',
        '--bootstrap='.$bootstrapPath,
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

it('locates composer autoload from an installed package layout', function (): void {
    $packageRoot = dirname(__DIR__, 3);
    $workspace = sys_get_temp_dir().'/deadcode-installed-worker-'.bin2hex(random_bytes(6));
    $installedBin = $workspace.'/vendor/deadcode/deadcode-laravel/bin';
    $bootstrapPath = $packageRoot.'/tests/Fixtures/Runtime/fixture-bootstrap-app.php';

    mkdir($installedBin, 0777, true);
    copy($packageRoot.'/bin/ox-runtime-worker.php', $installedBin.'/ox-runtime-worker.php');
    file_put_contents(
        $workspace.'/vendor/autoload.php',
        '<?php require '.var_export($packageRoot.'/vendor/autoload.php', true).';'.PHP_EOL,
    );

    $process = new Process([
        PHP_BINARY,
        $installedBin.'/ox-runtime-worker.php',
        '--bootstrap='.$bootstrapPath,
        '--once',
    ], $workspace);

    $process->setInput(FrameCodec::encode([
        'type' => 'task.run',
        'taskClass' => 'Tests\\Fixtures\\Runtime\\FixtureTask',
        'payload' => ['name' => 'installed'],
    ]));

    $process->mustRun();

    $frame = FrameCodec::decode($process->getOutput());

    expect($frame['type'])->toBe('task.completed');
    expect($frame['result']['data'])->toBe([
        'message' => 'hello installed',
    ]);

    \Illuminate\Support\Facades\File::deleteDirectory($workspace);
});

it('fails deterministically when the task class does not implement the runtime task contract', function (): void {
    $bootstrap = new WorkerBootstrap(app());

    expect(fn (): string => $bootstrap->run(FrameCodec::encode([
        'type' => 'task.run',
        'taskClass' => stdClass::class,
        'payload' => [],
    ])))->toThrow(
        \RuntimeException::class,
        sprintf('Worker task class [%s] must implement [%s].', stdClass::class, \Deadcode\Runtime\Contracts\Task::class),
    );
});

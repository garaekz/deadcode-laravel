<?php

declare(strict_types=1);

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\Supervisor\GoSupervisorProcessTransport;
use Tests\Fixtures\Runtime\FixtureTask;

it('emits a worker-executable task run frame with observable name and task class', function (): void {
    $packageRoot = dirname(__DIR__, 3);
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'deadcode-worker-transport-'.bin2hex(random_bytes(8));
    mkdir($tempDir, 0777, true);

    $capturedFramePath = $tempDir.DIRECTORY_SEPARATOR.'task-run-frame.json';
    $workerPath = $packageRoot.'/bin/ox-runtime-worker.php';
    $bootstrapPath = $packageRoot.'/tests/Fixtures/Runtime/fixture-bootstrap-app.php';
    $binary = makePortablePhpCommand($tempDir, 'worker-relay', sprintf(<<<'PHP'
require %s;

$input = (string) stream_get_contents(STDIN);
file_put_contents(%s, $input);

$process = new \Symfony\Component\Process\Process([
    PHP_BINARY,
    %s,
    '--bootstrap='.%s,
    '--once',
], %s);
$process->setInput($input);
$process->run();

if ($process->isSuccessful()) {
    fwrite(STDOUT, $process->getOutput());
}

fwrite(STDERR, $process->getErrorOutput());
exit($process->getExitCode() ?? 1);
PHP, var_export($packageRoot.'/vendor/autoload.php', true), var_export($capturedFramePath, true), var_export($workerPath, true), var_export($bootstrapPath, true), var_export($packageRoot, true)));

    $transport = new GoSupervisorProcessTransport($binary, 5);

    $frames = [];
    $result = $transport->run(new FixtureTask('demo'), function (array $frame) use (&$frames): void {
        $frames[] = $frame;
    });

    $capturedFrame = json_decode(trim((string) file_get_contents($capturedFramePath)), true, flags: JSON_THROW_ON_ERROR);

    expect($capturedFrame)->toMatchArray([
        'type' => 'task.run',
        'name' => 'fixture.task',
        'taskClass' => FixtureTask::class,
        'payload' => ['name' => 'demo'],
    ]);
    expect($capturedFrame['taskId'])->toBeString()->not->toBe('');
    expect($frames)->toHaveCount(2);
    expect($frames[0]['type'])->toBe('task.progress');
    expect($frames[0]['message'])->toBe('hello demo');
    expect($frames[0]['taskId'])->toBe($capturedFrame['taskId']);
    expect($frames[1]['type'])->toBe('task.completed');
    expect($frames[1]['taskId'])->toBe($capturedFrame['taskId']);
    expect($result['status'])->toBe('ok');
    expect($result['data'])->toBe(['message' => 'hello demo']);
});

it('streams supervisor frames before process completion and uses a unique task id per run', function (): void {
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'deadcode-transport-'.bin2hex(random_bytes(8));
    mkdir($tempDir, 0777, true);

    $signalPath = $tempDir.DIRECTORY_SEPARATOR.'callback.signal';
    $binary = makePortablePhpCommand($tempDir, 'fake-supervisor', <<<'PHP'
$input = trim((string) stream_get_contents(STDIN));
$frame = json_decode($input, true, flags: JSON_THROW_ON_ERROR);
$taskId = $frame['taskId'] ?? null;

fwrite(STDOUT, json_encode([
    'type' => 'task.started',
    'taskId' => $taskId,
    'name' => $frame['name'] ?? null,
], JSON_THROW_ON_ERROR) . PHP_EOL);
fflush(STDOUT);

$signalPath = $frame['payload']['signalPath'] ?? null;
$deadline = microtime(true) + 1.5;
while (! is_file($signalPath) && microtime(true) < $deadline) {
    usleep(10_000);
}

if (! is_file($signalPath)) {
    fwrite(STDERR, "callback signal not observed\n");
    exit(2);
}

fwrite(STDOUT, json_encode([
    'type' => 'task.completed',
    'taskId' => $taskId,
    'result' => [
        'status' => 'ok',
        'data' => ['taskId' => $taskId],
        'meta' => [],
    ],
], JSON_THROW_ON_ERROR) . PHP_EOL);
fflush(STDOUT);
PHP);

    $transport = new GoSupervisorProcessTransport($binary, 5);
    $task = new class($signalPath) implements Task
    {
        public function __construct(private readonly string $signalPath) {}

        public function name(): string
        {
            return 'deadcode.analyze_project';
        }

        public function payload(): array
        {
            return [
                'projectPath' => 'C:/repo',
                'signalPath' => $this->signalPath,
            ];
        }
    };

    $run = function () use ($signalPath, $task, $transport): array {
        @unlink($signalPath);

        $frames = [];
        $result = $transport->run($task, function (array $frame) use (&$frames, $signalPath): void {
            $frames[] = $frame;

            if (($frame['type'] ?? null) === 'task.started') {
                file_put_contents($signalPath, 'seen');
            }
        });

        return [$frames, $result];
    };

    [$firstFrames, $firstResult] = $run();
    [$secondFrames, $secondResult] = $run();

    expect($firstFrames)->toHaveCount(2);
    expect($secondFrames)->toHaveCount(2);
    expect($firstFrames[0]['type'])->toBe('task.started');
    expect($firstFrames[1]['type'])->toBe('task.completed');
    expect($firstFrames[0]['taskId'])->toBe($firstFrames[1]['taskId']);
    expect($secondFrames[0]['taskId'])->toBe($secondFrames[1]['taskId']);
    expect($firstResult['data']['taskId'])->toBe($firstFrames[0]['taskId']);
    expect($secondResult['data']['taskId'])->toBe($secondFrames[0]['taskId']);
    expect($firstFrames[0]['taskId'])->not->toBe('task-1');
    expect($secondFrames[0]['taskId'])->not->toBe('task-1');
    expect($firstFrames[0]['taskId'])->not->toBe($secondFrames[0]['taskId']);
});

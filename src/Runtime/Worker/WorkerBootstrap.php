<?php

declare(strict_types=1);

namespace Deadcode\Runtime\Worker;

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\Contracts\TaskHandler;
use Deadcode\Runtime\Protocol\FrameCodec;
use Illuminate\Contracts\Container\Container;

final readonly class WorkerBootstrap
{
    public function __construct(private Container $container) {}

    public function run(string $inputLine): string
    {
        $frame = FrameCodec::decode($inputLine);
        $task = new $frame['taskClass'](...$frame['payload']);

        assert($task instanceof Task);

        $handler = $this->container->make($task::class.'Handler');

        assert($handler instanceof TaskHandler);

        $context = new InMemoryTaskContext($frame['taskId'] ?? 'task-1');
        $result = $handler->handle($task, $context);

        return FrameCodec::encode([
            'type' => 'task.completed',
            'taskId' => $frame['taskId'] ?? 'task-1',
            'result' => [
                'status' => $result->status,
                'data' => $result->data,
                'meta' => $result->meta,
                'events' => $context->events(),
            ],
        ]);
    }
}

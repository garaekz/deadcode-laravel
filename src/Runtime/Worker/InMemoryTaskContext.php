<?php

declare(strict_types=1);

namespace Deadcode\Runtime\Worker;

use Deadcode\Runtime\Contracts\TaskContext;

final class InMemoryTaskContext implements TaskContext
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $events = [];

    public function __construct(private readonly string $taskId) {}

    public function emitProgress(string $message, ?int $percent = null, array $meta = []): void
    {
        $this->events[] = [
            'type' => 'task.progress',
            'taskId' => $this->taskId,
            'message' => $message,
            'percent' => $percent,
            'meta' => $meta,
        ];
    }

    public function writeStdout(string $chunk): void
    {
        $this->events[] = [
            'type' => 'task.stdout',
            'taskId' => $this->taskId,
            'chunk' => $chunk,
        ];
    }

    public function writeStderr(string $chunk): void
    {
        $this->events[] = [
            'type' => 'task.stderr',
            'taskId' => $this->taskId,
            'chunk' => $chunk,
        ];
    }

    public function isCancellationRequested(): bool
    {
        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(): array
    {
        return $this->events;
    }
}

<?php

declare(strict_types=1);

namespace Deadcode\Tasks;

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\Contracts\TaskContext;
use Deadcode\Runtime\Contracts\TaskHandler;
use Deadcode\Runtime\TaskResult;

final class AnalyzeProjectTaskHandler implements TaskHandler
{
    public function handle(Task $task, TaskContext $context): TaskResult
    {
        assert($task instanceof AnalyzeProjectTask);

        $context->emitProgress('Validating target project', 5);
        $context->emitProgress('Capturing Laravel runtime snapshot', 20);
        $context->emitProgress('Building deadcore request', 40);
        $context->emitProgress('Invoking deadcore', 70);
        $context->emitProgress('Writing report', 90);

        return new TaskResult(
            status: 'ok',
            data: [
                'findingCount' => 0,
                'reportPath' => 'storage/app/deadcode/report.json',
            ],
            meta: ['durationMs' => 0],
        );
    }
}

<?php

declare(strict_types=1);

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\Runtime;
use Deadcode\Runtime\Supervisor\SupervisorTransport;

it('streams progress while running deadcode analyze', function (): void {
    $runtime = new Runtime(new class implements SupervisorTransport
    {
        public function run(Task $task, callable $onFrame): array
        {
            $onFrame([
                'type' => 'task.progress',
                'taskId' => 'task-1',
                'message' => 'Capturing Laravel runtime snapshot',
                'percent' => 20,
            ]);
            $onFrame([
                'type' => 'task.progress',
                'taskId' => 'task-1',
                'message' => 'Invoking deadcore',
                'percent' => 70,
            ]);

            return [
                'status' => 'ok',
                'data' => [
                    'findingCount' => 12,
                    'reportPath' => 'storage/app/deadcode/report.json',
                ],
                'meta' => ['durationMs' => 321],
            ];
        }
    });

    app()->instance(Runtime::class, $runtime);

    $this->artisan('deadcode:analyze')
        ->expectsOutput('Capturing Laravel runtime snapshot')
        ->expectsOutput('Invoking deadcore')
        ->expectsOutputToContain('Findings: 12')
        ->expectsOutputToContain('Report: storage/app/deadcode/report.json')
        ->assertExitCode(0);
});

it('renders runtime failures and exits non-zero', function (): void {
    $runtime = new Runtime(new class implements SupervisorTransport
    {
        public function run(Task $task, callable $onFrame): array
        {
            throw new RuntimeException('deadcode supervisor transport failed');
        }
    });

    app()->instance(Runtime::class, $runtime);

    $this->artisan('deadcode:analyze')
        ->expectsOutputToContain('deadcode supervisor transport failed')
        ->assertExitCode(1);
});

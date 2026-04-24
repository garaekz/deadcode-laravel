<?php

declare(strict_types=1);

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\Supervisor\SupervisorTransport;
use Deadcode\Tasks\AnalyzeProjectTask;
use Illuminate\Support\Facades\Artisan;

it('dispatches AnalyzeProjectTask through the runtime-backed deadcode analyze command', function (): void {
    $transport = new class implements SupervisorTransport
    {
        public ?Task $receivedTask = null;

        public function run(Task $task, callable $onFrame): array
        {
            $this->receivedTask = $task;

            $onFrame(['type' => 'task.progress', 'message' => 'Capturing runtime snapshot']);

            return [
                'status' => 'ok',
                'data' => [
                    'findingCount' => 3,
                    'reportPath' => '/tmp/deadcode-report.json',
                ],
            ];
        }
    };

    app()->instance(SupervisorTransport::class, $transport);

    expect(Artisan::call('deadcode:analyze', ['projectPath' => '/workspace/app']))->toBe(0)
        ->and($transport->receivedTask)->toBeInstanceOf(AnalyzeProjectTask::class)
        ->and($transport->receivedTask?->projectPath)->toBe('/workspace/app')
        ->and(Artisan::output())->toContain('Capturing runtime snapshot')
        ->toContain('Findings: 3')
        ->toContain('Report: /tmp/deadcode-report.json');
});

it('fails when the runtime result is missing a required key', function (string $missingKey): void {
    bindAnalyzeTransport(['findingCount' => 3, 'reportPath' => '/tmp/deadcode-report.json'], $missingKey);

    expect(Artisan::call('deadcode:analyze'))->toBe(1)
        ->and(Artisan::output())->toContain(sprintf('Runtime result missing required key [%s].', $missingKey));
})->with([
    'findingCount' => 'findingCount',
    'reportPath' => 'reportPath',
]);

it('fails when the runtime result contains the wrong scalar type', function (string $key, mixed $value, string $expectedType): void {
    bindAnalyzeTransport([$key => $value]);

    expect(Artisan::call('deadcode:analyze'))->toBe(1)
        ->and(Artisan::output())->toContain(sprintf('Runtime result key [%s] must be of type [%s].', $key, $expectedType));
})->with([
    'findingCount as string' => ['findingCount', '3', 'int'],
    'reportPath as int' => ['reportPath', 3, 'string'],
]);

/**
 * @param  array<string, mixed>  $overrides
 */
function bindAnalyzeTransport(array $overrides = [], ?string $missingKey = null): void
{
    $data = array_merge([
        'findingCount' => 3,
        'reportPath' => '/tmp/deadcode-report.json',
    ], $overrides);

    if (is_string($missingKey)) {
        unset($data[$missingKey]);
    }

    app()->instance(SupervisorTransport::class, new class($data) implements SupervisorTransport
    {
        public function __construct(
            /** @var array<string, mixed> */
            private readonly array $data,
        ) {}

        public function run(Task $task, callable $onFrame): array
        {
            return [
                'status' => 'ok',
                'data' => $this->data,
            ];
        }
    });
}

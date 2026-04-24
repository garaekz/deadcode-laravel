<?php

declare(strict_types=1);

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\Runtime;
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
                    'analysisPath' => '/tmp/deadcode-analysis.json',
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
        ->toContain('Analysis: /tmp/deadcode-analysis.json');
});

it('runs through a supervisor binary resolved relative to the application base path', function (): void {
    $workspace = sys_get_temp_dir().'/deadcode-relative-supervisor-'.bin2hex(random_bytes(6));
    $projectRoot = $workspace.'/target-app';
    mkdir($projectRoot, 0777, true);

    makePortablePhpCommand($workspace.'/bin', 'deadcode-supervisor', <<<'PHP'
$frame = json_decode(trim((string) stream_get_contents(STDIN)), true, flags: JSON_THROW_ON_ERROR);

fwrite(STDOUT, json_encode([
    'type' => 'task.completed',
    'taskId' => $frame['taskId'],
    'result' => [
        'status' => 'ok',
        'data' => [
            'findingCount' => 1,
            'analysisPath' => $frame['payload']['projectPath'] . '/storage/app/deadcode/analysis.json',
        ],
        'meta' => [],
    ],
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL);
PHP);

    app()->setBasePath($workspace);
    config()->set('deadcode.supervisor_binary', 'bin/deadcode-supervisor');
    app()->forgetInstance(SupervisorTransport::class);
    app()->forgetInstance(Runtime::class);

    expect(Artisan::call('deadcode:analyze', ['projectPath' => $projectRoot]))->toBe(0)
        ->and(Artisan::output())->toContain('Analysis:')
        ->toContain('target-app/storage/app/deadcode/analysis.json');
});

it('fails when the runtime result is missing a required key', function (string $missingKey): void {
    bindAnalyzeTransport(['findingCount' => 3, 'analysisPath' => '/tmp/deadcode-analysis.json'], $missingKey);

    expect(Artisan::call('deadcode:analyze'))->toBe(1)
        ->and(Artisan::output())->toContain(sprintf('Runtime result missing required key [%s].', $missingKey));
})->with([
    'findingCount' => 'findingCount',
    'analysisPath' => 'analysisPath',
]);

it('fails when the runtime result contains the wrong scalar type', function (string $key, mixed $value, string $expectedType): void {
    bindAnalyzeTransport([$key => $value]);

    expect(Artisan::call('deadcode:analyze'))->toBe(1)
        ->and(Artisan::output())->toContain(sprintf('Runtime result key [%s] must be of type [%s].', $key, $expectedType));
})->with([
    'findingCount as string' => ['findingCount', '3', 'int'],
    'analysisPath as int' => ['analysisPath', 3, 'string'],
]);

/**
 * @param  array<string, mixed>  $overrides
 */
function bindAnalyzeTransport(array $overrides = [], ?string $missingKey = null): void
{
    $data = array_merge([
        'findingCount' => 3,
        'analysisPath' => '/tmp/deadcode-analysis.json',
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

<?php

declare(strict_types=1);

namespace Deadcode\Tasks;

use Deadcode\Runtime\Contracts\Task;
use Deadcode\Runtime\Contracts\TaskContext;
use Deadcode\Runtime\Contracts\TaskHandler;
use Deadcode\Runtime\TaskResult;
use InvalidArgumentException;
use JsonException;
use Oxhq\Oxcribe\Bridge\DeadCodeAnalysisRequestFactory;
use Oxhq\Oxcribe\Bridge\ProcessDeadCodeClient;
use Oxhq\Oxcribe\Contracts\RuntimeSnapshotFactory;
use Oxhq\Oxcribe\Data\DeadCodeAnalysisResponse;
use RuntimeException;

final class AnalyzeProjectTaskHandler implements TaskHandler
{
    public function __construct(
        private readonly RuntimeSnapshotFactory $runtimeSnapshotFactory,
        private readonly DeadCodeAnalysisRequestFactory $analysisRequestFactory,
        private readonly ProcessDeadCodeClient $deadCodeClient,
    ) {}

    public function handle(Task $task, TaskContext $context): TaskResult
    {
        assert($task instanceof AnalyzeProjectTask);

        $context->emitProgress('Validating target project', 5);
        $projectRoot = $this->validateProjectPath($task->projectPath);

        $context->emitProgress('Capturing Laravel runtime snapshot', 20);
        $runtime = $this->runtimeSnapshotFactory->make();
        $this->assertRuntimeMatchesProject($runtime->app->basePath, $projectRoot);

        $context->emitProgress('Building deadcore request', 40);
        $request = $this->analysisRequestFactory->make($runtime, $projectRoot);

        $context->emitProgress('Invoking deadcore', 70);
        $response = $this->deadCodeClient->analyze($request);

        $context->emitProgress('Writing report', 90);
        $analysisPath = $this->writeAnalysisPayload($projectRoot, $response);

        return new TaskResult(
            status: 'ok',
            data: [
                'findingCount' => count($response->findings),
                'analysisPath' => $analysisPath,
            ],
            meta: ['durationMs' => $response->meta->durationMs],
        );
    }

    private function validateProjectPath(string $projectPath): string
    {
        $resolved = realpath($projectPath);

        if ($resolved === false || ! is_dir($resolved)) {
            throw new InvalidArgumentException(sprintf(
                'Analyze project path must be an existing directory: %s',
                $projectPath,
            ));
        }

        return $resolved;
    }

    private function assertRuntimeMatchesProject(string $runtimeBasePath, string $projectRoot): void
    {
        $resolvedRuntimeBasePath = realpath($runtimeBasePath) ?: $runtimeBasePath;

        if ($this->normalizePath($resolvedRuntimeBasePath) === $this->normalizePath($projectRoot)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'The worker is bootstrapped for [%s], but the requested project path is [%s]. '.
            'Run deadcode:analyze from the target app or configure the supervisor to bootstrap that app before executing the task.',
            $resolvedRuntimeBasePath,
            $projectRoot,
        ));
    }

    private function normalizePath(string $path): string
    {
        return strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($path, '/\\')));
    }

    private function writeAnalysisPayload(string $projectRoot, DeadCodeAnalysisResponse $response): string
    {
        $reportDirectory = $projectRoot.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'deadcode';

        if (! is_dir($reportDirectory) && ! mkdir($reportDirectory, 0777, true) && ! is_dir($reportDirectory)) {
            throw new RuntimeException(sprintf('Unable to create deadcode report directory [%s].', $reportDirectory));
        }

        $analysisPath = $reportDirectory.DIRECTORY_SEPARATOR.'analysis.json';

        try {
            $json = json_encode(
                $response->jsonSerialize(),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode deadcode analysis JSON: '.$exception->getMessage(), 0, $exception);
        }

        file_put_contents($analysisPath, $json.PHP_EOL);

        return $analysisPath;
    }
}

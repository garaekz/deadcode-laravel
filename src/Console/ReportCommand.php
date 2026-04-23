<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Console;

use Illuminate\Console\Command;
use Oxhq\Oxcribe\Bridge\AnalysisRequestFactory;
use Oxhq\Oxcribe\Contracts\OxinferClient;
use Oxhq\Oxcribe\Contracts\RuntimeSnapshotFactory;
use Oxhq\Oxcribe\Data\AnalysisResponse;
use Oxhq\Oxcribe\Data\Diagnostic;
use Oxhq\Oxcribe\Data\RouteMatch;

final class ReportCommand extends Command
{
    use WritesJsonOutput;

    protected $signature = 'deadcode:report {--project-root=} {--write=} {--pretty}';

    protected $description = 'Produce a local dead code report from the current Laravel runtime and deadcore analysis';

    public function handle(
        RuntimeSnapshotFactory $runtimeSnapshotFactory,
        AnalysisRequestFactory $analysisRequestFactory,
        OxinferClient $oxinferClient,
    ): int {
        $runtime = $runtimeSnapshotFactory->make();
        $request = $analysisRequestFactory->make($runtime, $this->option('project-root'));
        $response = $oxinferClient->analyze($request);

        return $this->writeJsonPayload(
            $this->reportPayload($runtime->app->basePath, $response),
            (string) $this->option('write'),
            (bool) $this->option('pretty'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function reportPayload(string $projectRoot, AnalysisResponse $response): array
    {
        return [
            'contractVersion' => 'deadcode.report.v1',
            'projectRoot' => $projectRoot,
            'requestId' => $response->requestId,
            'runtimeFingerprint' => $response->runtimeFingerprint,
            'status' => $response->status,
            'summary' => [
                'routesInspected' => count($response->routeMatches),
                'diagnosticCount' => count($response->diagnostics),
                'partial' => (bool) ($response->meta['partial'] ?? false),
            ],
            'diagnostics' => array_map(
                static fn (Diagnostic $diagnostic): array => [
                    'code' => $diagnostic->code,
                    'severity' => $diagnostic->severity,
                    'scope' => $diagnostic->scope,
                    'message' => $diagnostic->message,
                    'routeId' => $diagnostic->routeId,
                    'actionKey' => $diagnostic->actionKey,
                    'file' => $diagnostic->file,
                    'line' => $diagnostic->line,
                ],
                $response->diagnostics,
            ),
            'routeMatches' => array_map(
                static fn (RouteMatch $routeMatch): array => [
                    'routeId' => $routeMatch->routeId,
                    'actionKind' => $routeMatch->actionKind,
                    'matchStatus' => $routeMatch->matchStatus,
                    'actionKey' => $routeMatch->actionKey,
                    'reasonCode' => $routeMatch->reasonCode,
                ],
                $response->routeMatches,
            ),
        ];
    }
}

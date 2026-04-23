<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Console;

use Illuminate\Console\Command;
use Oxhq\Oxcribe\Bridge\DeadCodeAnalysisRequestFactory;
use Oxhq\Oxcribe\Bridge\ProcessDeadCodeClient;
use Oxhq\Oxcribe\Contracts\RuntimeSnapshotFactory;
use Oxhq\Oxcribe\Data\DeadCodeAnalysisResponse;
use Oxhq\Oxcribe\Data\DeadCodeEntrypoint;
use Oxhq\Oxcribe\Data\DeadCodeFinding;
use Oxhq\Oxcribe\Data\DeadCodeRemovalChangeSet;
use Oxhq\Oxcribe\Data\DeadCodeSymbol;

final class ReportCommand extends Command
{
    use WritesJsonOutput;

    protected $signature = 'deadcode:report {--write=} {--pretty}';

    protected $description = 'Produce a local dead code report from the current Laravel runtime and deadcore analysis';

    public function handle(
        RuntimeSnapshotFactory $runtimeSnapshotFactory,
        DeadCodeAnalysisRequestFactory $analysisRequestFactory,
        ProcessDeadCodeClient $deadCodeClient,
    ): int {
        $runtime = $runtimeSnapshotFactory->make();
        $request = $analysisRequestFactory->make($runtime);
        $response = $deadCodeClient->analyze($request);

        return $this->writeJsonPayload(
            $this->reportPayload($runtime->app->basePath, $response),
            (string) $this->option('write'),
            (bool) $this->option('pretty'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function reportPayload(string $projectRoot, DeadCodeAnalysisResponse $response): array
    {
        $reachableSymbolCount = count(array_filter(
            $response->symbols,
            static fn (DeadCodeSymbol $symbol): bool => $symbol->reachableFromRuntime,
        ));

        return [
            'contractVersion' => 'deadcode.report.v1',
            'projectRoot' => $projectRoot,
            'requestId' => $response->requestId,
            'status' => $response->status,
            'meta' => [
                'durationMs' => $response->meta->durationMs,
                'cacheHits' => $response->meta->cacheHits,
                'cacheMisses' => $response->meta->cacheMisses,
            ],
            'summary' => [
                'entrypointCount' => count($response->entrypoints),
                'symbolCount' => count($response->symbols),
                'reachableSymbolCount' => $reachableSymbolCount,
                'unreachableSymbolCount' => count($response->symbols) - $reachableSymbolCount,
                'findingCount' => count($response->findings),
                'removalChangeCount' => count($response->removalPlan->changeSets),
            ],
            'entrypoints' => array_map(
                static fn (DeadCodeEntrypoint $entrypoint): array => [
                    'kind' => $entrypoint->kind,
                    'symbol' => $entrypoint->symbol,
                    'source' => $entrypoint->source,
                ],
                $response->entrypoints,
            ),
            'symbols' => array_map(
                static fn (DeadCodeSymbol $symbol): array => array_filter([
                    'kind' => $symbol->kind,
                    'symbol' => $symbol->symbol,
                    'file' => $symbol->file,
                    'reachableFromRuntime' => $symbol->reachableFromRuntime,
                    'startLine' => $symbol->startLine,
                    'endLine' => $symbol->endLine,
                ], static fn (mixed $value): bool => $value !== null),
                $response->symbols,
            ),
            'findings' => array_map(
                static fn (DeadCodeFinding $finding): array => array_filter([
                    'symbol' => $finding->symbol,
                    'category' => $finding->category,
                    'confidence' => $finding->confidence,
                    'file' => $finding->file,
                    'startLine' => $finding->startLine,
                    'endLine' => $finding->endLine,
                ], static fn (mixed $value): bool => $value !== null),
                $response->findings,
            ),
            'removalPlan' => [
                'changeSets' => array_map(
                    static fn (DeadCodeRemovalChangeSet $changeSet): array => [
                        'file' => $changeSet->file,
                        'symbol' => $changeSet->symbol,
                        'startLine' => $changeSet->startLine,
                        'endLine' => $changeSet->endLine,
                    ],
                    $response->removalPlan->changeSets,
                ),
            ],
        ];
    }
}

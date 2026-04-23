<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Data;

use InvalidArgumentException;
use JsonException;
use JsonSerializable;

final readonly class DeadCodeAnalysisResponse implements JsonSerializable
{
    public const CONTRACT_VERSION = 'deadcode.analysis.v1';

    /**
     * @param  list<DeadCodeEntrypoint>  $entrypoints
     * @param  list<DeadCodeSymbol>  $symbols
     * @param  list<DeadCodeFinding>  $findings
     */
    public function __construct(
        public string $contractVersion,
        public string $requestId,
        public string $status,
        public DeadCodeAnalysisMeta $meta,
        public array $entrypoints,
        public array $symbols,
        public array $findings,
        public DeadCodeRemovalPlan $removalPlan,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $contractVersion = (string) ($payload['contractVersion'] ?? '');
        if ($contractVersion !== self::CONTRACT_VERSION) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported deadcode analysis response contract version [%s]; expected [%s].',
                $contractVersion,
                self::CONTRACT_VERSION,
            ));
        }

        return new self(
            contractVersion: $contractVersion,
            requestId: (string) ($payload['requestId'] ?? ''),
            status: (string) ($payload['status'] ?? 'failed'),
            meta: DeadCodeAnalysisMeta::fromArray((array) ($payload['meta'] ?? [])),
            entrypoints: array_map(
                static fn (array $entrypoint): DeadCodeEntrypoint => DeadCodeEntrypoint::fromArray($entrypoint),
                array_values((array) ($payload['entrypoints'] ?? [])),
            ),
            symbols: array_map(
                static fn (array $symbol): DeadCodeSymbol => DeadCodeSymbol::fromArray($symbol),
                array_values((array) ($payload['symbols'] ?? [])),
            ),
            findings: array_map(
                static fn (array $finding): DeadCodeFinding => DeadCodeFinding::fromArray($finding),
                array_values((array) ($payload['findings'] ?? [])),
            ),
            removalPlan: DeadCodeRemovalPlan::fromArray((array) ($payload['removalPlan'] ?? [])),
        );
    }

    /**
     * @throws JsonException
     */
    public static function fromJson(string $json): self
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return self::fromArray($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contractVersion' => $this->contractVersion,
            'requestId' => $this->requestId,
            'status' => $this->status,
            'meta' => $this->meta->jsonSerialize(),
            'entrypoints' => array_map(
                static fn (DeadCodeEntrypoint $entrypoint): array => $entrypoint->jsonSerialize(),
                $this->entrypoints,
            ),
            'symbols' => array_map(
                static fn (DeadCodeSymbol $symbol): array => $symbol->jsonSerialize(),
                $this->symbols,
            ),
            'findings' => array_map(
                static fn (DeadCodeFinding $finding): array => $finding->jsonSerialize(),
                $this->findings,
            ),
            'removalPlan' => $this->removalPlan->jsonSerialize(),
        ];
    }
}

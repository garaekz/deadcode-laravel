<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Data;

use JsonSerializable;

final readonly class DeadCodeAnalysisMeta implements JsonSerializable
{
    public function __construct(
        public int $durationMs,
        public int $cacheHits,
        public int $cacheMisses,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            durationMs: (int) ($payload['duration_ms'] ?? $payload['durationMs'] ?? 0),
            cacheHits: (int) ($payload['cache_hits'] ?? $payload['cacheHits'] ?? 0),
            cacheMisses: (int) ($payload['cache_misses'] ?? $payload['cacheMisses'] ?? 0),
        );
    }

    /**
     * @return array<string, int>
     */
    public function jsonSerialize(): array
    {
        return [
            'duration_ms' => $this->durationMs,
            'cache_hits' => $this->cacheHits,
            'cache_misses' => $this->cacheMisses,
        ];
    }
}

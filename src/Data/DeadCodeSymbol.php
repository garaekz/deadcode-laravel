<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Data;

use JsonSerializable;

final readonly class DeadCodeSymbol implements JsonSerializable
{
    public function __construct(
        public string $kind,
        public string $symbol,
        public string $file,
        public bool $reachableFromRuntime,
        public ?int $startLine = null,
        public ?int $endLine = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            kind: (string) ($payload['kind'] ?? ''),
            symbol: (string) ($payload['symbol'] ?? ''),
            file: (string) ($payload['file'] ?? ''),
            reachableFromRuntime: (bool) ($payload['reachableFromRuntime'] ?? false),
            startLine: isset($payload['startLine']) ? (int) $payload['startLine'] : null,
            endLine: isset($payload['endLine']) ? (int) $payload['endLine'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'kind' => $this->kind,
            'symbol' => $this->symbol,
            'file' => $this->file,
            'reachableFromRuntime' => $this->reachableFromRuntime,
            'startLine' => $this->startLine,
            'endLine' => $this->endLine,
        ], static fn (mixed $value): bool => $value !== null);
    }
}

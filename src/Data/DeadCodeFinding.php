<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Data;

use JsonSerializable;

final readonly class DeadCodeFinding implements JsonSerializable
{
    public function __construct(
        public string $symbol,
        public string $category,
        public string $confidence,
        public string $file,
        public ?int $startLine = null,
        public ?int $endLine = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            symbol: (string) ($payload['symbol'] ?? ''),
            category: (string) ($payload['category'] ?? ''),
            confidence: (string) ($payload['confidence'] ?? ''),
            file: (string) ($payload['file'] ?? ''),
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
            'symbol' => $this->symbol,
            'category' => $this->category,
            'confidence' => $this->confidence,
            'file' => $this->file,
            'startLine' => $this->startLine,
            'endLine' => $this->endLine,
        ], static fn (mixed $value): bool => $value !== null);
    }
}

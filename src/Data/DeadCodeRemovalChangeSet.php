<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Data;

use JsonSerializable;

final readonly class DeadCodeRemovalChangeSet implements JsonSerializable
{
    public function __construct(
        public string $file,
        public string $symbol,
        public int $startLine,
        public int $endLine,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            file: (string) ($payload['file'] ?? ''),
            symbol: (string) ($payload['symbol'] ?? ''),
            startLine: (int) ($payload['start_line'] ?? $payload['startLine'] ?? 0),
            endLine: (int) ($payload['end_line'] ?? $payload['endLine'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'file' => $this->file,
            'symbol' => $this->symbol,
            'start_line' => $this->startLine,
            'end_line' => $this->endLine,
        ];
    }
}

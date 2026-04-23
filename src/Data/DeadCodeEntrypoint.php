<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Data;

use JsonSerializable;

final readonly class DeadCodeEntrypoint implements JsonSerializable
{
    public function __construct(
        public string $kind,
        public string $symbol,
        public string $source,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            kind: (string) ($payload['kind'] ?? ''),
            symbol: (string) ($payload['symbol'] ?? ''),
            source: (string) ($payload['source'] ?? ''),
        );
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
            'kind' => $this->kind,
            'symbol' => $this->symbol,
            'source' => $this->source,
        ];
    }
}

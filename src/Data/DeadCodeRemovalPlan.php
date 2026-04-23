<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Data;

use JsonSerializable;

final readonly class DeadCodeRemovalPlan implements JsonSerializable
{
    /**
     * @param  list<DeadCodeRemovalChangeSet>  $changeSets
     */
    public function __construct(
        public array $changeSets,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            changeSets: array_map(
                static fn (array $changeSet): DeadCodeRemovalChangeSet => DeadCodeRemovalChangeSet::fromArray($changeSet),
                array_values((array) ($payload['changeSets'] ?? [])),
            ),
        );
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function jsonSerialize(): array
    {
        return [
            'changeSets' => array_map(
                static fn (DeadCodeRemovalChangeSet $changeSet): array => $changeSet->jsonSerialize(),
                $this->changeSets,
            ),
        ];
    }
}

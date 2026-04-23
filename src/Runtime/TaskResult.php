<?php

declare(strict_types=1);

namespace Deadcode\Runtime;

final readonly class TaskResult
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $status,
        public array $data = [],
        public array $meta = [],
    ) {}
}

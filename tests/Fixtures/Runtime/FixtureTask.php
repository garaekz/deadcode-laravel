<?php

declare(strict_types=1);

namespace Tests\Fixtures\Runtime;

use Deadcode\Runtime\Contracts\Task;

final readonly class FixtureTask implements Task
{
    public function __construct(public string $name) {}

    public function name(): string
    {
        return 'fixture.task';
    }

    public function payload(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}

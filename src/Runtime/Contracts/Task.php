<?php

declare(strict_types=1);

namespace Deadcode\Runtime\Contracts;

interface Task
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}

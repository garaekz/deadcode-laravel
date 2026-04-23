<?php

declare(strict_types=1);

namespace Deadcode\Tasks;

use Deadcode\Runtime\Contracts\Task;

final readonly class AnalyzeProjectTask implements Task
{
    public function __construct(
        public string $projectPath,
        public string $format = 'json',
    ) {}

    public function name(): string
    {
        return 'deadcode.analyze_project';
    }

    public function payload(): array
    {
        return [
            'projectPath' => $this->projectPath,
            'format' => $this->format,
        ];
    }
}

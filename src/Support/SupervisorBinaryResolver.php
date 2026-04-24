<?php

declare(strict_types=1);

namespace Deadcode\Support;

use RuntimeException;

final class SupervisorBinaryResolver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function resolve(array $config, string $workingDirectory): string
    {
        $configured = trim((string) ($config['supervisor_binary'] ?? ''));

        if ($configured === '') {
            throw new RuntimeException(
                'Unable to find the supervisor binary: deadcode.supervisor_binary is empty. '.
                'Set DEADCODE_SUPERVISOR_BINARY or configure deadcode.supervisor_binary.'
            );
        }

        foreach ($this->pathCandidates($configured, $workingDirectory) as $candidate) {
            if ($this->isRunnableFile($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf(
            'Unable to find the supervisor binary at "%s". Set DEADCODE_SUPERVISOR_BINARY or configure deadcode.supervisor_binary to an executable path.',
            $this->normalizePath($configured, $workingDirectory),
        ));
    }

    /**
     * @return list<string>
     */
    private function pathCandidates(string $binary, string $workingDirectory): array
    {
        $candidate = $this->normalizePath($binary, $workingDirectory);
        $candidates = [$candidate];

        if (PHP_OS_FAMILY === 'Windows' && pathinfo($candidate, PATHINFO_EXTENSION) === '') {
            foreach (['.exe', '.cmd', '.bat'] as $suffix) {
                $candidates[] = $candidate.$suffix;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function normalizePath(string $binary, string $workingDirectory): string
    {
        if (str_starts_with($binary, '~/')) {
            $home = getenv('HOME') ?: '';

            return ($home !== '' ? rtrim($home, DIRECTORY_SEPARATOR) : '').DIRECTORY_SEPARATOR.ltrim(substr($binary, 2), DIRECTORY_SEPARATOR);
        }

        if (
            str_starts_with($binary, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $binary) === 1
        ) {
            return $binary;
        }

        return rtrim($workingDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($binary, DIRECTORY_SEPARATOR);
    }

    private function isRunnableFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return is_executable($path);
        }

        if (is_executable($path)) {
            return true;
        }

        return in_array(strtolower('.'.pathinfo($path, PATHINFO_EXTENSION)), ['.exe', '.cmd', '.bat'], true);
    }
}

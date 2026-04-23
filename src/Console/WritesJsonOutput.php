<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Console;

use JsonException;

trait WritesJsonOutput
{
    /**
     * @param  array<string, mixed>  $payload
     */
    protected function writeJsonPayload(array $payload, string $target = '', bool $pretty = false): int
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0));
        } catch (JsonException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($target !== '') {
            file_put_contents($target, $json.PHP_EOL);
            $this->info(sprintf('Payload written to %s', $target));

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }
}

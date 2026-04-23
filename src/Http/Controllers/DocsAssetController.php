<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Http\Controllers;

use Illuminate\Http\Response;

final class DocsAssetController
{
    public function __invoke(string $asset): Response
    {
        $asset = basename($asset);
        $path = realpath(__DIR__.'/../../../resources/dist/'.$asset);

        abort_unless(is_string($path) && str_starts_with($path, realpath(__DIR__.'/../../../resources/dist') ?: ''), 404);
        abort_unless(is_file($path), 404);

        $contentType = str_ends_with($asset, '.css')
            ? 'text/css; charset=UTF-8'
            : 'application/javascript; charset=UTF-8';

        return response(file_get_contents($path), 200, [
            'Content-Type' => $contentType,
        ]);
    }
}

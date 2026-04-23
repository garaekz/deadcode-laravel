<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Oxhq\Oxcribe\OxcribeManager;

final class OpenApiDocumentController
{
    public function __construct(private readonly OxcribeManager $manager) {}

    public function __invoke(): JsonResponse
    {
        return response()->json(
            $this->manager->exportOpenApi(),
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}

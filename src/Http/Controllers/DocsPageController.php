<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Http\Controllers;

use Illuminate\Contracts\View\View;

final class DocsPageController
{
    public function __invoke(): View
    {
        return view('oxcribe::docs', [
            'title' => (string) config('oxcribe.openapi.info.title', 'Oxcribe Docs'),
            'viewerCssUrl' => route('oxcribe.docs.asset', ['asset' => 'docs-viewer.css']),
            'viewerJsUrl' => route('oxcribe.docs.asset', ['asset' => 'docs-viewer.js']),
            'openApiUrl' => route('oxcribe.openapi'),
            'payloadUrl' => route('oxcribe.docs.payload'),
        ]);
    }
}

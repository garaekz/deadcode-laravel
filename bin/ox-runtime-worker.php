#!/usr/bin/env php
<?php

declare(strict_types=1);

use Deadcode\Runtime\Worker\WorkerBootstrap;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;

$autoloadPath = null;

foreach ([
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
    getcwd().'/vendor/autoload.php',
] as $candidate) {
    if (is_file($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

if (! is_string($autoloadPath)) {
    throw new \RuntimeException('The worker could not locate Composer autoload. Run it from a Laravel app or set the worker script path to an installed package copy.');
}

require $autoloadPath;

$bootstrapPath = null;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--bootstrap=')) {
        $bootstrapPath = substr($argument, strlen('--bootstrap='));
        break;
    }
}

$bootstrapPath ??= getenv('DEADCODE_WORKER_BOOTSTRAP') ?: null;

if (! is_string($bootstrapPath) || $bootstrapPath === '') {
    throw new \RuntimeException(
        'The worker requires a bootstrap file path via --bootstrap=<path> or DEADCODE_WORKER_BOOTSTRAP.'
    );
}

$resolvedBootstrapPath = realpath($bootstrapPath);

if (! is_string($resolvedBootstrapPath) || ! is_file($resolvedBootstrapPath)) {
    throw new \RuntimeException(sprintf('Worker bootstrap file [%s] was not found.', $bootstrapPath));
}

$app = require $resolvedBootstrapPath;

if (! $app instanceof Container) {
    throw new \RuntimeException(sprintf(
        'Worker bootstrap file [%s] must return an instance of [%s].',
        $resolvedBootstrapPath,
        Container::class,
    ));
}

if ($app instanceof Application && ! $app->isBooted()) {
    if ($app->bound(ConsoleKernel::class)) {
        $app->make(ConsoleKernel::class)->bootstrap();
    } else {
        $app->boot();
    }
}

$bootstrap = new WorkerBootstrap($app);
$once = in_array('--once', $argv, true);

while (($line = fgets(STDIN)) !== false) {
    fwrite(STDOUT, $bootstrap->run($line));

    if ($once) {
        break;
    }
}

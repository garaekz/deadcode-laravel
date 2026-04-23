#!/usr/bin/env php
<?php

declare(strict_types=1);

use Deadcode\Runtime\Worker\WorkerBootstrap;
use Illuminate\Foundation\Application;

require __DIR__.'/../vendor/autoload.php';

$app = new Application(dirname(__DIR__));
$bootstrap = new WorkerBootstrap($app);
$once = in_array('--once', $argv, true);

while (($line = fgets(STDIN)) !== false) {
    fwrite(STDOUT, $bootstrap->run($line));

    if ($once) {
        break;
    }
}

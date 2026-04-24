<?php

declare(strict_types=1);

namespace Deadcode\Providers;

use Deadcode\Runtime\Runtime;
use Deadcode\Runtime\Supervisor\GoSupervisorProcessTransport;
use Deadcode\Runtime\Supervisor\SupervisorTransport;
use Deadcode\Support\SupervisorBinaryResolver;
use Deadcode\Tasks\AnalyzeProjectTask;
use Deadcode\Tasks\AnalyzeProjectTaskHandler;
use Illuminate\Support\ServiceProvider;

final class DeadcodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/deadcode.php', 'deadcode');

        $this->app->singleton(SupervisorTransport::class, function ($app): SupervisorTransport {
            return new GoSupervisorProcessTransport(
                binary: (new SupervisorBinaryResolver)->resolve(
                    (array) $app['config']->get('deadcode', []),
                    $app->basePath(),
                ),
                timeout: (int) $app['config']->get('deadcode.supervisor_timeout'),
            );
        });

        $this->app->singleton(Runtime::class, fn ($app): Runtime => new Runtime(
            $app->make(SupervisorTransport::class),
        ));

        $this->app->bind(AnalyzeProjectTask::class.'Handler', AnalyzeProjectTaskHandler::class);
    }
}

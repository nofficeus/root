<?php

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\ArgvInput;

class DebugServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local') && (!$this->app->runningInConsole() || !$this->excludedCommand())) {
            $this->app->register(IdeHelperServiceProvider::class);
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function excludedCommand()
    {
        $command = new ArgvInput();

        return in_array($command->getFirstArgument(), [
            "key:generate",
            "package:discover",
            "server:install",
            "server:publish"
        ]);
    }
}

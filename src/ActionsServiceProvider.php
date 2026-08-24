<?php

namespace Asterism\Actions;

use Asterism\Actions\Console\Commands\MakeActionCommand;
use Illuminate\Support\ServiceProvider;

class ActionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/actions.php', 'actions');

        $channel = $this->app['config']->get('actions.log_channel', 'actions');

        $this->app['config']->set("logging.channels.{$channel}", array_merge(
            $this->app['config']->get('actions.default_log_channel_config', []),
            $this->app['config']->get("logging.channels.{$channel}", [])
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/actions.php' => $this->app->configPath('actions.php'),
            ], 'actions-config');

            $this->commands([
                MakeActionCommand::class,
            ]);
        }
    }
}

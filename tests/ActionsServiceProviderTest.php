<?php

namespace Asterism\Actions\Tests;

use Asterism\Actions\ActionsServiceProvider;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

class ActionsServiceProviderTest extends TestCase
{
    public function test_default_actions_log_channel_is_registered(): void
    {
        $channel = config('logging.channels.actions');

        $this->assertNotNull($channel);
        $this->assertSame('daily', $channel['driver']);
        $this->assertSame(14, $channel['days']);
    }

    public function test_it_does_not_clobber_other_log_channels(): void
    {
        $this->assertNotNull(config('logging.channels.stack'));
        $this->assertNotNull(config('logging.channels.single'));
    }

    public function test_host_defined_channel_config_wins_over_the_package_default(): void
    {
        config(['logging.channels.actions' => ['path' => '/custom/path/actions.log']]);

        (new ActionsServiceProvider($this->app))->register();

        $this->assertSame('/custom/path/actions.log', config('logging.channels.actions.path'));
        $this->assertSame('daily', config('logging.channels.actions.driver'));
    }

    public function test_log_channel_name_is_configurable(): void
    {
        $this->assertSame('actions', config('actions.log_channel'));
    }

    public function test_make_action_command_is_registered(): void
    {
        $this->assertArrayHasKey(
            'make:action',
            $this->app->make(ConsoleKernel::class)->all()
        );
    }
}

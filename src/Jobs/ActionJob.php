<?php

namespace Asterism\Actions\Jobs;

use Asterism\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param Action<mixed> $action
     * @return void
     */
    public function __construct(protected Action $action)
    {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->action->handle();
    }
}

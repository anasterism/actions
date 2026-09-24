<?php

namespace Asterism\Actions;

use Asterism\Actions\Jobs\ActionJob;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * @template TBuilderReturn
 */
class ActionBuilder
{
    use Conditionable;

    /**
     * @var Action<TBuilderReturn>
     */
    protected Action $action;
    private bool $benchmark = false;

    /**
     * @param Action<TBuilderReturn> $action
     */
    public function __construct(Action $action)
    {
        $this->action = $action;
    }

    public function actingAs(Authenticatable $user): static
    {
        $this->action->actor = $user;

        return $this;
    }

    public function enableBenchmark(): static
    {
        $this->benchmark = true;

        return $this;
    }

    /**
     * Execute the underlying action.
     *
     * @param mixed ...$arguments
     * @return TBuilderReturn
     */
    public function execute(...$arguments)
    {
        $args = $this->prepareArguments($arguments);
        $this->action->arguments = $this->action->arguments->merge($args);

        $this->action->validate();

        if (!$this->shouldSkipAuthorization()) {
            $this->action->checkAuthorization();
        }

        if ($this->benchmark) {
            return $this->runBenchmark();
        }

        return $this->action->handle();
    }

    /**
     * Validate and authorize the underlying action now, then run it on the queue.
     *
     * @param mixed ...$arguments
     */
    public function dispatch(...$arguments): void
    {
        $args = $this->prepareArguments($arguments);
        $this->action->arguments = $this->action->arguments->merge($args);

        $this->action->validate();
        $this->action->checkAuthorization();

        ActionJob::dispatch($this->action);
    }

    private function prepareArguments(array $arguments): array
    {
        $flattenedArgs = reset($arguments);
        $arguments     = count($arguments) === 1 && is_array($flattenedArgs) ? $flattenedArgs : $arguments;

        if (!empty($arguments) && array_is_list($arguments)) {
            throw new InvalidArgumentException('Action arguments must be passed as named arguments or an associative array');
        }

        return $arguments;
    }

    private function shouldSkipAuthorization(): bool
    {
        return app()->runningInConsole() && $this->action->actor === null;
    }

    private function runBenchmark(): mixed
    {
        // make sure garbage collection doesn't muck anything up
        gc_collect_cycles();

        // initial measurements
        memory_reset_peak_usage();
        $startMemory = memory_get_usage();
        $startTime   = microtime(true);

        // do an action
        $result      = $this->action->handle();

        // feast upon your shame
        $peakMemory  = memory_get_peak_usage();
        $endTime     = microtime(true);

        // format results, at least you can say it is easy on the eyes
        $executionTime = number_format(($endTime - $startTime) * 1000, 2) . ' ms';

        $allocatedMemory = $peakMemory - $startMemory;
        $memoryUsage = ($allocatedMemory > 1048576)
            ? number_format($allocatedMemory / 1048576, 2) . ' MB'
            : number_format($allocatedMemory / 1024, 2) . ' KB';

        // hello darkness my old friend...
        Log::channel(config('actions.log_channel', 'actions'))
            ->info(sprintf(
                "⚡ BENCHMARK ➔ %-50s ➔ %10s ➔ %10s",
                $this->action::class,
                $executionTime,
                $memoryUsage
            ));

        return $result;
    }
}

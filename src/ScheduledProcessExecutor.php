<?php

namespace Paveldanilin\ProcessExecutor;

use React\EventLoop\Loop;

class ScheduledProcessExecutor extends ProcessExecutor implements ScheduledExecutorServiceInterface
{
    public function schedule(float $period, \Closure $task, ?float $timeout = null): ScheduledFutureInterface
    {
        $scheduledFuture = new ScheduledFuture();
        $timer = Loop::addPeriodicTimer($period, function () use($task, $timeout, $scheduledFuture) {
            $this->submit($task, $timeout)->then(function ($value) use ($scheduledFuture) {
                $scheduledFuture->fulfill($value);
            }, function ($reason) use($scheduledFuture)  {
                $scheduledFuture->reject($reason);
            });
        });
        $scheduledFuture->onCancelled(function () use($timer) {
            Loop::cancelTimer($timer);
        });
        return $scheduledFuture;
    }
}

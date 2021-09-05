<?php

namespace Paveldanilin\ProcessExecutor;

use React\EventLoop\Loop;

class ScheduledProcessExecutor extends ProcessExecutor implements ScheduledExecutorServiceInterface
{
    public function schedule(float $period, \Closure $task, callable $onSuccess, ?callable $onError = null, ?float $timeout = null): void
    {
        Loop::addPeriodicTimer($period, function () use($task, $timeout, $onSuccess, $onError) {
            $this->submit($task, $timeout)->then(function ($value) use ($onSuccess) {
                $onSuccess($value);
            }, function ($reason) use($onError)  {
                if (null !== $onError) {
                    $onError($reason);
                }
            });
        });
    }
}

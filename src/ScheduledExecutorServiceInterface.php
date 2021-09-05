<?php

namespace Paveldanilin\ProcessExecutor;

interface ScheduledExecutorServiceInterface extends ExecutorServiceInterface
{
    public function schedule(float $period, \Closure $task, callable $onSuccess, ?callable $onError = null, ?float $timeout = null): void;
}

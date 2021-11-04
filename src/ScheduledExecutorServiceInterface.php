<?php

namespace Paveldanilin\ProcessExecutor;

interface ScheduledExecutorServiceInterface extends ExecutorServiceInterface
{
    public function schedule(float $period, \Closure $task, ?float $timeout = null): ScheduledFutureInterface;
}

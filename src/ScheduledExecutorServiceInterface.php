<?php

namespace Paveldanilin\ProcessExecutor;

use Paveldanilin\ProcessExecutor\Future\ScheduledFutureInterface;

interface ScheduledExecutorServiceInterface extends ExecutorServiceInterface
{
    public function schedule(float $period, \Closure $task, ?float $timeout = null): ScheduledFutureInterface;
    public function start(): void;
    public function stop(): void;
}

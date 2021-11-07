<?php

namespace Paveldanilin\ProcessExecutor;

use Paveldanilin\ProcessExecutor\Future\ScheduledFutureInterface;

interface ScheduledExecutorServiceInterface extends ExecutorServiceInterface
{
    /**
     * Creates a cron task.
     * A cron expression supports the additional macros:
     * @yearly, @annually - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
     * @monthly - Run once a month, midnight, first of month - 0 0 1 * *
     * @weekly - Run once a week, midnight on Sun - 0 0 * * 0
     * @daily - Run once a day, midnight - 0 0 * * *
     * @hourly - Run once an hour, first minute - 0 * * * *
     *
     * @param string $expression The cron expression
     * @param \Closure $task
     * @param float|null $timeout
     */
    public function cron(string $expression, \Closure $task, ?float $timeout = null): void;

    /**
     * @param float $period The interval after which this task will be executed, in seconds
     * @param \Closure $task
     * @param float|null $timeout
     * @return ScheduledFutureInterface
     */
    public function schedule(float $period, \Closure $task, ?float $timeout = null): ScheduledFutureInterface;
    public function start(): void;
    public function stop(): void;
}

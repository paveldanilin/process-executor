<?php

namespace Paveldanilin\ProcessExecutor;

use Paveldanilin\ProcessExecutor\Future\ScheduledFuture;
use Paveldanilin\ProcessExecutor\Future\ScheduledFutureInterface;
use React\EventLoop\Loop;

class ScheduledProcessExecutor extends ProcessExecutor implements ScheduledExecutorServiceInterface
{
    private const MAX_CRON_JOBS = 200;
    private const STATE_RUNNING = 0;
    private const STATE_STOPPED = 1;

    private int $state = self::STATE_STOPPED;
    /** @var array<CronJob>  */
    private array $cronJobs = [];

    public function cron(string $expression, \Closure $task, ?float $timeout = null): void
    {
        if (\count($this->cronJobs) > self::MAX_CRON_JOBS) {
            throw new \LogicException('The maximum of cron jobs reached');
        }
        $this->cronJobs[] = new CronJob($expression, $task, $timeout);
    }

    public function schedule(float $period, \Closure $task, ?float $timeout = null): ScheduledFutureInterface
    {
        $scheduledFuture = new ScheduledFuture();
        $taskTimer = Loop::addPeriodicTimer($period, function () use($task, $timeout, $scheduledFuture) {
            $this->submit($task, $timeout)->then(function ($value) use ($scheduledFuture) {
                $scheduledFuture->fulfill($value);
            }, function ($reason) use($scheduledFuture)  {
                $scheduledFuture->reject($reason);
            });
        });
        $scheduledFuture->onCancelled(function () use($taskTimer) {
            Loop::cancelTimer($taskTimer);
        });
        return $scheduledFuture;
    }

    public function start(): void
    {
        if (self::STATE_RUNNING === $this->state) {
            return;
        }
        $this->state = self::STATE_RUNNING;
        $this->startCron();
        Loop::run();
    }

    public function stop(): void
    {
        if (self::STATE_STOPPED === $this->state) {
            return;
        }
        $this->state = self::STATE_STOPPED;
        Loop::stop();
    }

    public function waitAll(): void
    {
        throw new \RuntimeException('Not implemented');
    }

    private function startCron(): void
    {
        if (0 === \count($this->cronJobs)) {
            return;
        }

        Loop::addPeriodicTimer(1, function () {
            foreach ($this->cronJobs as $cronTask) {
                if ($cronTask->isDue()) {
                    $this->execute($cronTask->getTask(), $cronTask->getTimeout());
                }
            }
        });
    }
}

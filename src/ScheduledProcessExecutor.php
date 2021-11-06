<?php

namespace Paveldanilin\ProcessExecutor;

use Paveldanilin\ProcessExecutor\Future\ScheduledFuture;
use Paveldanilin\ProcessExecutor\Future\ScheduledFutureInterface;
use React\EventLoop\Loop;

class ScheduledProcessExecutor extends ProcessExecutor implements ScheduledExecutorServiceInterface
{
    private const STATE_RUNNING = 0;
    private const STATE_STOPPED = 1;

    private int $state = self::STATE_STOPPED;

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
}

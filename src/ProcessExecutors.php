<?php

namespace Paveldanilin\ProcessExecutor;

use React\EventLoop\Loop;

if (\function_exists('pcntl_signal')) {
    $signalHandler = static function ($signal) {
        foreach (ProcessExecutors::getExecutors() as $executor) {
            $executor->shutdown();
        }
        exit;
    };
    \pcntl_signal(SIGTERM, $signalHandler);
    \pcntl_signal(SIGINT, $signalHandler);
}

abstract class ProcessExecutors
{
    private static bool $scheduleTimeoutChecked = false;
    private static array $executors = [];

    public static function newSingleExecutor(): ExecutorInterface
    {
        $executor = new ProcessExecutor(1, null);
        static::$executors[] = $executor;
        static::registerTimeoutChecker();
        return $executor;
    }

    public static function newFixedPoolExecutor(int $maxPoolSize): ExecutorServiceInterface
    {
        $executor = new ProcessExecutor($maxPoolSize);
        static::$executors[] = $executor;
        static::registerTimeoutChecker();
        return $executor;
    }

    public static function newQueuedPoolExecutor(int $maxPoolSize, TaskQueueInterface $queue): ExecutorServiceInterface
    {
        $executor = new ProcessExecutor($maxPoolSize, $queue);
        static::$executors[] = $executor;
        static::registerTimeoutChecker();
        return $executor;
    }

    public static function newScheduledPoolExecutor(int $maxPoolSize, ?TaskQueueInterface $queue): ScheduledExecutorServiceInterface
    {
        $executor = new ScheduledProcessExecutor($maxPoolSize, $queue);
        static::$executors[] = $executor;
        static::registerTimeoutChecker();
        return $executor;
    }

    /**
     * @return array<ExecutorInterface>
     */
    public static function getExecutors(): array
    {
        return static::$executors;
    }

    private static function registerTimeoutChecker(): void
    {
        if (true === static::$scheduleTimeoutChecked) {
            return;
        }
        static::$scheduleTimeoutChecked = true;
        Loop::addPeriodicTimer(1, function() {
            foreach (static::$executors as $executor) {
                $executor->checkTimeout();
            }
        });
    }
}

<?php

namespace Paveldanilin\ProcessExecutor;

use React\Promise\PromiseInterface;

interface ExecutorServiceInterface extends ExecutorInterface
{
    public function submit(\Closure $task, ?float $timeout = null): PromiseInterface;
    public function getPoolSize(): int;
    public function getMaxPoolSize(): int;
}

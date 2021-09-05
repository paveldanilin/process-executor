<?php

namespace Paveldanilin\ProcessExecutor;

interface ExecutorInterface
{
    public function execute(\Closure $task, ?float $timeout = null): void;
    public function checkTimeout(): void;
    public function shutdown(): void;
}

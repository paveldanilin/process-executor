<?php

namespace Paveldanilin\ProcessExecutor;

interface RejectedExecutionHandlerInterface
{
    public function rejectedExecution(\Closure $closure, ProcessExecutor $processExecutor): void;
}

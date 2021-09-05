<?php

namespace Paveldanilin\ProcessExecutor;

interface FixedTaskQueueInterface extends TaskQueueInterface
{
    public function getMaxSize(): int;
}

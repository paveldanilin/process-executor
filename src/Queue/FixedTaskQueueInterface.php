<?php

namespace Paveldanilin\ProcessExecutor\Queue;

interface FixedTaskQueueInterface extends TaskQueueInterface
{
    public function getMaxSize(): int;
}

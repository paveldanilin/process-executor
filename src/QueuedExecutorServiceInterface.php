<?php

namespace Paveldanilin\ProcessExecutor;

use Paveldanilin\ProcessExecutor\Queue\TaskQueueInterface;

interface QueuedExecutorServiceInterface
{
    public function getQueue(): ?TaskQueueInterface;
}

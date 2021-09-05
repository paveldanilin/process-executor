<?php

namespace Paveldanilin\ProcessExecutor;

interface QueuedExecutorServiceInterface
{
    public function getQueue(): ?TaskQueueInterface;
}

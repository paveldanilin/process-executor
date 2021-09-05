<?php

namespace Paveldanilin\ProcessExecutor;

interface TaskQueueInterface
{
    public function enqueue(Task $task): void;
    public function dequeue(): ?Task;
    public function getSize(): int;
}

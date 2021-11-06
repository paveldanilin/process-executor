<?php

namespace Paveldanilin\ProcessExecutor\Queue;

interface TaskQueueInterface
{
    public function enqueue(Task $task): void;
    public function dequeue(): ?Task;
    public function getSize(): int;
}

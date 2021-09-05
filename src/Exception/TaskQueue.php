<?php

namespace Paveldanilin\ProcessExecutor\Exception;

use Paveldanilin\ProcessExecutor\Task;
use Paveldanilin\ProcessExecutor\TaskQueueInterface;

final class TaskQueue implements TaskQueueInterface
{
    private \SplQueue $queue;

    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    public function enqueue(Task $task): void
    {
        $this->queue->enqueue($task);
    }

    public function dequeue(): ?Task
    {
        return $this->queue->pop();
    }

    public function getSize(): int
    {
        return $this->queue->count();
    }
}

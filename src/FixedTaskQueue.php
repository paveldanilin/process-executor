<?php

namespace Paveldanilin\ProcessExecutor;

final class FixedTaskQueue implements FixedTaskQueueInterface
{
    private int $maxQueueSize;
    private \SplQueue $queue;

    public function __construct(int $maxQueueSize)
    {
        $this->maxQueueSize = $maxQueueSize;
        $this->queue = new \SplQueue();
    }

    public function enqueue(Task $task): void
    {
        if ($this->queue->count() >= $this->maxQueueSize) {
            throw new \OverflowException();
        }
        $this->queue->enqueue($task);
    }

    public function dequeue(): ?Task
    {
        if (0 === $this->queue->count()) {
            return null;
        }
        return $this->queue->pop();
    }

    public function getSize(): int
    {
        return $this->queue->count();
    }

    public function getMaxSize(): int
    {
        return $this->maxQueueSize;
    }
}

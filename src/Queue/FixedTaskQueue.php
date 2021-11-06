<?php

namespace Paveldanilin\ProcessExecutor\Queue;

final class FixedTaskQueue implements FixedTaskQueueInterface
{
    private const MAX_SIZE = 5000;

    private int $maxQueueSize;
    private \SplQueue $queue;

    public function __construct(int $maxQueueSize)
    {
        $this->maxQueueSize = $maxQueueSize;
        if ($this->maxQueueSize <= 0) {
            $this->maxQueueSize = 1;
        } elseif ($this->maxQueueSize > self::MAX_SIZE) {
            $this->maxQueueSize = self::MAX_SIZE;
        }
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

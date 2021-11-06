<?php

namespace Paveldanilin\ProcessExecutor\Queue;

use React\Promise\Deferred;

final class Task
{
    private ?float $timeout;
    private \Closure $closure;
    private ?Deferred $deferred;

    public function __construct(\Closure $task, ?float $timeout, ?Deferred $deferred)
    {
        $this->closure = $task;
        $this->timeout = $timeout;
        $this->deferred = $deferred;
    }

    public function getClosure(): \Closure
    {
        return $this->closure;
    }

    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    public function getDeferred(): ?Deferred
    {
        return $this->deferred;
    }

    public function isDeferred(): bool
    {
        return null !== $this->deferred;
    }
}

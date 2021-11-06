<?php

namespace Paveldanilin\ProcessExecutor\Future;


final class ScheduledFuture implements ScheduledFutureInterface
{
    /** @var null|callable */
    private $onFulfilledCallable;
    /** @var null|callable */
    private $onRejectedCallable;
    /** @var callable */
    private $onCancelledCallable;
    private bool $cancelled = false;

    public function onCancelled(callable $onCancelled): void
    {
        $this->onCancelledCallable = $onCancelled;
    }

    public function onRejected(?callable $onRejected): self
    {
        $this->onRejectedCallable = $onRejected;
        return $this;
    }

    public function reject($reason): void
    {
        $callback = $this->onRejectedCallable;
        if (null !== $callback) {
            $callback($reason, $this);
        }
    }

    public function onFulfilled(?callable $onFulfilled): self
    {
        $this->onFulfilledCallable = $onFulfilled;
        return $this;
    }

    public function fulfill($value): void
    {
        $callback = $this->onFulfilledCallable;
        if (null !== $callback) {
            $callback($value, $this);
        }
    }

    public function cancel(): void
    {
        if ($this->isCancelled()) {
            return;
        }
        $callback = $this->onCancelledCallable;
        $callback();
        $this->cancelled = true;
    }

    public function isCancelled(): bool
    {
       return  $this->cancelled;
    }
}

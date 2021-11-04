<?php

namespace Paveldanilin\ProcessExecutor;

interface ScheduledFutureInterface extends CancellableFutureInterface
{
    public function onRejected(?callable $onRejected): self;
    public function onFulfilled(?callable $onFulfilled): self;
}

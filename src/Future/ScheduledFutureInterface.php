<?php

namespace Paveldanilin\ProcessExecutor\Future;

interface ScheduledFutureInterface extends CancellableFutureInterface
{
    public function onRejected(?callable $onRejected): self;
    public function onFulfilled(?callable $onFulfilled): self;
}

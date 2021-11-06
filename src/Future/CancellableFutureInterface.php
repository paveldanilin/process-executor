<?php

namespace Paveldanilin\ProcessExecutor\Future;

interface CancellableFutureInterface
{
    public function isCancelled(): bool;
    public function cancel(): void;
}

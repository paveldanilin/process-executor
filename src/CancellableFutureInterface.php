<?php

namespace Paveldanilin\ProcessExecutor;

interface CancellableFutureInterface
{
    public function isCancelled(): bool;
    public function cancel(): void;
}

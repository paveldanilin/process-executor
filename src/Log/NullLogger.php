<?php

namespace Paveldanilin\ProcessExecutor\Log;

class NullLogger extends AbstractLogger
{
    protected function doLog(string $message): void
    {
    }
}

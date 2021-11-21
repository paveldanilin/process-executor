<?php

namespace Paveldanilin\ProcessExecutor\Log;

use React\Stream\WritableStreamInterface;
use React\Stream\WritableResourceStream;

class StreamLogger extends AbstractLogger
{
    private ?WritableStreamInterface $stream;

    public function __construct($stream)
    {
        $this->stream = new WritableResourceStream($stream);
    }

    public function __destruct()
    {
        $this->stream->close();
    }

    protected function doLog(string $message): void
    {
        $this->stream->write($message);
    }
}

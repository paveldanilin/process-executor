<?php

namespace Paveldanilin\ProcessExecutor\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\Stream\WritableStreamInterface;
use React\Stream\WritableResourceStream;

final class Logger implements LoggerInterface, \Paveldanilin\ProcessExecutor\Log\LoggerInterface
{
    private string $filename;
    private ?WritableStreamInterface $stream;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->stream = null;
    }

    public function __destruct()
    {
        if (null !== $this->stream) {
            $this->stream->close();
        }
    }

    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->createStreamIfNull();

        $context['event_time'] = (new \DateTime())->format('d-m-Y\TH:i:s');
        $context['level'] = \strtoupper($level);

        $message = '[{event_time}] [{level}] ' . $message;

        $this->stream->write($this->interpolate($message, $context) . PHP_EOL);
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!\is_array($val) && (!\is_object($val) || \method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return \strtr($message, $replace);
    }

    private function createStreamIfNull(): void
    {
        if (null !== $this->stream) {
            return;
        }
        $this->stream = new WritableResourceStream(\fopen($this->filename, 'ab'));
    }
}

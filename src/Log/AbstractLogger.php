<?php

namespace Paveldanilin\ProcessExecutor\Log;


abstract class AbstractLogger extends \Psr\Log\AbstractLogger
{
    private int $intLevel = LevelHelper::ERROR;

    abstract protected function doLog(string $message): void;

    public function setLevel(string $level): AbstractLogger
    {
        $this->intLevel = LevelHelper::getIntValue($level);
        return $this;
    }

    public function log($level, $message, array $context = []): void
    {
        if (LevelHelper::getIntValue($level) < $this->intLevel) {
            return;
        }

        $context['event_time'] = (new \DateTime())->format('d-m-Y\TH:i:s');
        $context['level'] = \strtoupper($level);

        $message = '[{event_time}] [{level}]    ' . $message;

        $this->doLog($this->interpolate($message, $context) . PHP_EOL);
    }

    protected function interpolate(string $message, array $context): string
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
}

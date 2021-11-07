<?php

namespace Paveldanilin\ProcessExecutor;

use Cron\CronExpression;

final class CronJob
{
    private string $expression;
    private \Closure $task;
    private ?float $timeout;
    private int $nextRun;

    public function __construct(string $expression, \Closure $task, ?float $timeout)
    {
        if (!CronExpression::isValidExpression($expression)) {
            throw new \InvalidArgumentException('Bad cron expression');
        }
        $this->expression = $expression;
        $this->task = $task;
        $this->timeout = $timeout;
        $this->nextRun = $this->getNextRunTimestamp();
    }

    public function isDue(): bool
    {
        if ($this->nextRun <= (new \DateTime())->getTimestamp()) {
            $this->nextRun = $this->getNextRunTimestamp();
            return true;
        }
        return false;
    }

    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    public function getTask(): \Closure
    {
        return $this->task;
    }

    private function getNextRunTimestamp(): int
    {
        return (new CronExpression($this->expression))->getNextRunDate()->getTimestamp();
    }
}

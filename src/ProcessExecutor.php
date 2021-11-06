<?php

namespace Paveldanilin\ProcessExecutor;

use Opis\Closure\SerializableClosure;
use Paveldanilin\ProcessExecutor\Exception\ProcessExecutionException;
use Paveldanilin\ProcessExecutor\Exception\RejectedExecutionException;
use Paveldanilin\ProcessExecutor\Queue\Task;
use Paveldanilin\ProcessExecutor\Queue\TaskQueueInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

class ProcessExecutor implements ExecutorServiceInterface, QueuedExecutorServiceInterface
{
    private const MAX_CONCURRENCY = 50;
    private const DEFAULT_MAX_POOL_SIZE = 4;

    /** @var array<Process>  */
    private array $pool;
    private string $vendorDir;
    private int $maxPoolSize;
    private ?TaskQueueInterface $queue;
    private ?RejectedExecutionHandlerInterface $rejectedExecutionHandler;

    public function __construct(int $maxPoolSize = 0, ?TaskQueueInterface $queue = null, ?RejectedExecutionHandlerInterface $rejectedExecutionHandler = null)
    {
        $this->maxPoolSize = $this->filterMaxPoolSize($maxPoolSize);
        $this->vendorDir = '';
        $this->queue = $queue;
        $this->rejectedExecutionHandler = $rejectedExecutionHandler;
        $this->pool = [];
        for ($i = 0; $i < $this->maxPoolSize; $i++) {
            $this->pool[$i] = new Process([]);
        }
    }

    public function execute(\Closure $task, ?float $timeout = null): void
    {
        $freePID = $this->getFreeProcess();
        if (null === $freePID) {
            if (null === $this->queue) {
                if (null === $this->rejectedExecutionHandler) {
                    throw new RejectedExecutionException('A task cannot be accepted for execution');
                } else {
                    $this->rejectedExecutionHandler->rejectedExecution($task, $this);
                }
            } else {
                $this->queue->enqueue(new Task($task, $timeout, null));
                return;
            }
        }

        $this->pool[$freePID] = new PhpProcess($this->buildScript($task));
        $this->pool[$freePID]->setTimeout($timeout);
        $this->pool[$freePID]->start(function () {
            $this->processQueue();
        });
    }

    public function submit(\Closure $task, ?float $timeout = null): PromiseInterface
    {
        $deferred = new Deferred();
        $freePID = $this->getFreeProcess();
        if (null === $freePID) {
            if (null === $this->queue) {
                throw new RejectedExecutionException('A task cannot be accepted for execution');
            } else {
                $this->queue->enqueue(new Task($task, $timeout, $deferred));
                return $deferred->promise();
            }
        }

        $this->pool[$freePID] = new PhpProcess($this->buildScriptWithReturn($task));
        $this->pool[$freePID]->setTimeout($timeout);
        $this->pool[$freePID]->start(function ($type, $buffer) use($deferred) {
            if (Process::ERR === $type) {
                $deferred->reject(new ProcessExecutionException($buffer));
            } else {
                $data = \unserialize(\base64_decode($buffer), ['allowed_classes' => true]);
                if ($data instanceof \Throwable) {
                    $deferred->reject($data);
                } else {
                    $deferred->resolve($data);
                }
            }
            $this->processQueue();
        });
        return $deferred->promise();
    }

    public function shutdown(): void
    {
        foreach ($this->pool as $proc) {
            if ($proc->isRunning()) {
                $proc->signal(SIGKILL);
            }
        }
    }

    public function checkTimeout(): void
    {
        foreach ($this->pool as $proc) {
            if ($proc->isRunning()) {
                try {
                    $proc->checkTimeout();
                } catch (ProcessTimedOutException $exception) {
                    $proc->stop();
                    //throw $exception;
                }
            }
        }
    }

    public function getPoolSize(): int
    {
        $size = 0;
        foreach ($this->pool as $proc) {
            if ($proc->isRunning()) {
                $size++;
            }
        }
        return $size;
    }

    public function getMaxPoolSize(): int
    {
        return $this->maxPoolSize;
    }

    public function getQueue(): ?TaskQueueInterface
    {
        return $this->queue;
    }

    public function waitAll(): void
    {
        for (;;) {
            $this->checkTimeout();
            $emptyQueue = null === $this->queue || 0 === $this->queue->getSize();
            if ($emptyQueue && 0 === $this->getPoolSize()) {
                return;
            }
        }
    }

    private function processQueue(): void
    {
        if (null === $this->queue || 0 === $this->queue->getSize()) {
            return;
        }

        $freePID = $this->getFreeProcess();
        if (null === $freePID) {
            return;
        }

        /** @var Task|null $task */
        $task = $this->queue->dequeue();
        if (null === $task) {
            return;
        }

        $script = $task->isDeferred() ? $this->buildScriptWithReturn($task->getClosure()) : $this->buildScript($task->getClosure());
        $this->pool[$freePID] = new PhpProcess($script);
        $this->pool[$freePID]->setTimeout($task->getTimeout());
        $this->pool[$freePID]->start(function ($type, $rawData) use($task) {
            if ($task->isDeferred()) {
                if (Process::ERR === $type) {
                    $task->getDeferred()->reject(new ProcessExecutionException($rawData));
                } else {
                    $data = \unserialize(\base64_decode($rawData), ['allowed_classes' => true]);
                    if ($data instanceof \Throwable) {
                        $task->getDeferred()->reject($data);
                    } else {
                        $task->getDeferred()->resolve($data);
                    }
                }
            }
            $this->processQueue();
        });
    }

    private function getFreeProcess(): ?int
    {
        foreach ($this->pool as $pid => $proc) {
            if (!$proc->isRunning()) {
                return $pid;
            }
        }
        return null;
    }

    protected function serialize(\Closure $closure): string
    {
        return \base64_encode(\serialize(new SerializableClosure($closure)));
    }

    protected function getVendorDir(): string
    {
        if (empty($this->vendorDir)) {
            $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
            $this->vendorDir = \dirname($reflection->getFileName(), 2);
        }
        return $this->vendorDir;
    }

    private function filterMaxPoolSize(int $concurrency): int
    {
        if ($concurrency <= 0) {
            return self::DEFAULT_MAX_POOL_SIZE;
        }
        if ($concurrency > self::MAX_CONCURRENCY) {
            return self::DEFAULT_MAX_POOL_SIZE;
        }
        return $concurrency;
    }

    private function buildScriptWithReturn(\Closure $task): string
    {
        return '<?php 
        require "' . $this->getVendorDir() . '/autoload.php";
        $closure = \unserialize(\base64_decode("' . $this->serialize($task) . '"))->getClosure();
        \ob_start();
        try {
            $ret = $closure();
            \ob_clean();
            echo \base64_encode(\serialize($ret));   
        } catch (\Throwable $t) {
            \ob_clean();
            echo \base64_encode(\serialize($t));
        } finally {
            \ob_flush();
        }
        exit(0);
?>';
    }

    private function buildScript(\Closure $task): string
    {
        return '<?php 
        require "' . $this->getVendorDir() . '/autoload.php";
        $closure = \unserialize(\base64_decode("' . $this->serialize($task) . '"))->getClosure();
        \ob_start();
        try {
            $closure();
            \ob_clean(); 
        } catch (\Throwable $t) {
            \ob_clean();
            echo \base64_encode(\serialize($t));
        } finally {
            echo 0;
            \ob_flush();
        }
        exit(0);
?>';
    }
}

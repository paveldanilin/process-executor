<?php

namespace Paveldanilin\ProcessExecutor;

use Opis\Closure\SerializableClosure;
use Paveldanilin\ProcessExecutor\Exception\ProcessExecutionException;
use Paveldanilin\ProcessExecutor\Exception\RejectedExecutionException;
use Paveldanilin\ProcessExecutor\Log\LoggerInterface;
use Paveldanilin\ProcessExecutor\Log\NullLogger;
use Paveldanilin\ProcessExecutor\Queue\Task;
use Paveldanilin\ProcessExecutor\Queue\TaskQueueInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

class ProcessExecutor implements ExecutorServiceInterface, QueuedExecutorServiceInterface
{
    private const MAX_CONCURRENCY = 50;
    private const DEFAULT_POOL_SIZE = 4;

    /** @var array<Process>  */
    private array $pool;
    private string $vendorDir;
    private int $maxPoolSize;
    private ?TaskQueueInterface $queue;
    private ?RejectedExecutionHandlerInterface $rejectedExecutionHandler;
    protected LoggerInterface $logger;

    public function __construct(int $maxPoolSize = 0,
                                ?TaskQueueInterface $queue = null,
                                ?RejectedExecutionHandlerInterface $rejectedExecutionHandler = null,
                                ?LoggerInterface $logger = null)
    {
        $this->maxPoolSize = $this->filterMaxPoolSize($maxPoolSize);
        $this->vendorDir = '';
        $this->queue = $queue;
        $this->rejectedExecutionHandler = $rejectedExecutionHandler;
        $this->logger = $logger ?? new NullLogger();
        $this->pool = [];
        for ($i = 0; $i < $this->maxPoolSize; $i++) {
            $this->pool[$i] = new Process([]);
        }
    }

    public function execute(\Closure $task, ?float $timeout = null): void
    {
        $freePID = $this->getFreeProcess();
        if (null === $freePID) {
            $this->executeNoProcessHandler($task, $timeout);
            return;
        }

        $this->startChildProcess($freePID, $task, $timeout);
    }

    public function submit(\Closure $task, ?float $timeout = null): PromiseInterface
    {
        $deferred = new Deferred();
        $freePID = $this->getFreeProcess();
        if (null === $freePID) {
            return $this->submitNoProcessHandler($task, $timeout, $deferred);
        }

        $this->startChildProcessWithDeferred($freePID, $task, $timeout, $deferred);

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
            $this->logger->info('The queue is empty, nothing to execute', ['method' => $this->getMethodName(__METHOD__)]);
            return;
        }

        $freePID = $this->getFreeProcess();
        if (null === $freePID) {
            $this->logger->warning('Can not process a queue since the pool is busy', ['method' => $this->getMethodName(__METHOD__)]);
            return;
        }

        /** @var Task|null $task */
        $task = $this->queue->dequeue();
        if (null === $task) {
            $this->logger->warning('Dequeued an empty task, nothing to execute', ['method' => $this->getMethodName(__METHOD__)]);
            return;
        }

        if ($task->isDeferred()) {
            $this->startChildProcessWithDeferred(
                $freePID,
                $task->getClosure(),
                $task->getTimeout(),
                $task->getDeferred()
            );
        } else {
            $this->startChildProcess(
                $freePID,
                $task->getClosure(),
                $task->getTimeout()
            );
        }
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
            return self::DEFAULT_POOL_SIZE;
        }
        if ($concurrency > self::MAX_CONCURRENCY) {
            return self::MAX_CONCURRENCY;
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

    private function startChildProcess(int $pid, \Closure $task, ?float $timeout): void
    {
        $method = __METHOD__;
        $this->logger->info('[proc-{pid}] Going to start a child process  timeout={timeout},method={method}', [
            'pid' => $pid,
            'timeout' => $timeout,
            'method' => $this->getMethodName($this->getMethodName($method)),
        ]);

        $this->pool[$pid] = new PhpProcess($this->buildScript($task));
        $this->pool[$pid]->setTimeout($timeout);
        $this->pool[$pid]->start(function () use($pid, $method) {
            $this->logger->info('[proc-{pid}] A child process is finished  method={method}', [
                'pid' => $pid,
                'method' => $this->getMethodName($method),
            ]);
            $this->processQueue();
        });
    }

    private function startChildProcessWithDeferred(int $pid, \Closure $task, ?float $timeout, Deferred $deferred): void
    {
        $method = __METHOD__;
        $this->logger->info('[proc-{pid}] Going to start a child process  timeout={timeout},method={method}', [
            'pid' => $pid,
            'timeout' => $timeout,
            'method' => $this->getMethodName($method),
        ]);

        $this->pool[$pid] = new PhpProcess($this->buildScriptWithReturn($task));
        $this->pool[$pid]->setTimeout($timeout);
        $this->pool[$pid]->start(function ($type, $buffer) use($deferred, $pid, $method) {
            if (Process::ERR === $type) {
                $this->logger->error('[proc-{pid}] A child process is failed  method={method},buffer={buffer}', [
                    'pid' => $pid,
                    'method' => $this->getMethodName($method),
                    'buffer' => $buffer,
                ]);
                $deferred->reject(new ProcessExecutionException($buffer));
            } else {
                $data = \unserialize(\base64_decode($buffer), ['allowed_classes' => true]);
                if ($data instanceof \Throwable) {
                    $this->logger->warning('[proc-{pid}] A child process is failed with an exception  method={method},exception={exception}', [
                        'pid' => $pid,
                        'method' => $this->getMethodName($method),
                        'exception' => $data->getMessage(),
                    ]);
                    $deferred->reject($data);
                } else {
                    $this->logger->info('[proc-{pid}] A child process is finished with a result  method={method}', [
                        'pid' => $pid,
                        'method' => $this->getMethodName($method),
                    ]);
                    $deferred->resolve($data);
                }
            }
            $this->processQueue();
        });
    }

    private function executeNoProcessHandler(\Closure $task, ?float $timeout): void
    {
        if (null === $this->queue) {
            if (null === $this->rejectedExecutionHandler) {
                $this->logger->critical('A task cannot be accepted for execution', ['method' => $this->getMethodName(__METHOD__)]);
                throw new RejectedExecutionException('A task cannot be accepted for execution');
            }
            $this->logger->warning('Going to call the rejected execution handler', ['method' => $this->getMethodName(__METHOD__)]);
            $this->rejectedExecutionHandler->rejectedExecution($task, $this);
            return;
        }

        try {
            $this->queue->enqueue(new Task($task, $timeout, null));
            $this->logger->info('A task has been enqueued at position [' . ($this->queue->getSize() - 1) . ']', ['method' => $this->getMethodName(__METHOD__)]);
        } catch (\OverflowException $exception) {
            $this->logger->critical('Could not enqueue a task: the queue is full', ['method' => $this->getMethodName(__METHOD__)]);
            throw $exception;
        }
    }

    private function submitNoProcessHandler(\Closure $task, ?float $timeout, Deferred $deferred): PromiseInterface
    {
        if (null === $this->queue) {
            if (null === $this->rejectedExecutionHandler) {
                $this->logger->critical('A task cannot be accepted for execution', ['method' => $this->getMethodName(__METHOD__)]);
                throw new RejectedExecutionException('A task cannot be accepted for execution');
            }
            $this->logger->warning('Going to call the rejected execution handler', ['method' => $this->getMethodName(__METHOD__)]);
            $this->rejectedExecutionHandler->rejectedExecution($task, $this);
            return new FulfilledPromise();
        }

        try {
            $this->queue->enqueue(new Task($task, $timeout, $deferred));
            $this->logger->info('A task has been enqueued at position [' . ($this->queue->getSize() - 1) . ']', ['method' => $this->getMethodName(__METHOD__)]);
        } catch (\OverflowException $exception) {
            $this->logger->critical('Could not enqueue a task: the queue is full', ['method' => $this->getMethodName(__METHOD__)]);
            throw $exception;
        }

        return $deferred->promise();
    }

    private function getMethodName(string $method): string
    {
        $parts = \explode('\\', $method);
        return \end($parts);
    }
}

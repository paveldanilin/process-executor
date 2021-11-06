# ProcessExecutor

A ProcessExecutor allows you to execute closure in the sub-process.

Scheduled execution
```php
    $executor = ProcessExecutors::newScheduledPoolExecutor(4);

    $periodSec = 1; 
    
    $executor->schedule($periodSec, function () {
        // This code block will be executed in sub-process
        \sleep(5);
        $result = \time();
        return $result;
    })->onFulfilled(function ($time) {
        echo "Time: $time\n";
    });

    $executor->start();
```

Do tasks in parallel 
```php
    $executor = ProcessExecutors::newFixedPoolExecutor(4);

    $executor->submit(function () {
        sleep(2);
        return 1;
    })->then(function ($data) {
        print $data . "\n";
    });
    
    $timeoutSec = 2;
    $executor->submit(function () {
        sleep(10);
        return 2;
    }, $timeoutSec)->then(function ($data) {
        print $data . "\n";
    });
    
    $executor->submit(function () {
        sleep(5);
        return 3;
    })->then(function ($data) {
        print $data . "\n";
    });
    
    $executor->waitAll();
```

See more [examples](./examples).

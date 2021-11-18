# ProcessExecutor

ProcessExecutor allows you to execute the closure in the sub-process.

Due to the limitations of the serialization process, it is not possible to pass any PHP resource to the closure.
But we can overcome this drawback by opening a resource inside closure (e.g. open a db connection or file).

Scheduled execution
```php
    $executor = ProcessExecutors::newScheduledPoolExecutor(4);

    $periodSec = 1; 
    
    $executor->schedule($periodSec, function () {
        // Do a time-consuming task
        // This code block will be executed in sub-process
        \sleep(5);
        $result = \time();
        return $result;
    })->onFulfilled(function ($time) {
        echo "Time: $time\n";
    });

    $executor->start();
```

Parallel execution
```php
    $executor = ProcessExecutors::newFixedPoolExecutor(4);

    $executor->submit(function () {
        // Do a time-consuming task
        sleep(2);
        return 1;
    })->then(function ($data) {
        print $data . "\n";
    });
    
    // With timeout
    $timeoutSec = 2;
    $executor->submit(function () {
        // Do a time-consuming task
        sleep(10);
        return 2;
    }, $timeoutSec)->then(function ($data) {
        print $data . "\n";
    });
    
    $executor->submit(function () {
        // Do a time-consuming task
        sleep(5);
        return 3;
    })->then(function ($data) {
        print $data . "\n";
    });
    
    $executor->waitAll();
```


Cron
```php
    $executor = ProcessExecutors::newScheduledPoolExecutor(4);

    // Hourly
    $executor->cron('0 * * * *', function () {
        // Do a time-consuming task
        \file_put_contents('./cron.out', (new \DateTime())->format('H:i:s') . "\n", FILE_APPEND);
    });

    $executor->start();
```

See more [examples](./examples).

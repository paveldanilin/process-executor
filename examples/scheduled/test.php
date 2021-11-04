<?php

use Paveldanilin\ProcessExecutor\ProcessExecutors;

require '../../vendor/autoload.php';

$executor = ProcessExecutors::newScheduledPoolExecutor(10, null);
$runCount = 0;

$executor->schedule(1, function () {
    \sleep(1);
    return \time();
}, 20)->onFulfilled(function ($time, \Paveldanilin\ProcessExecutor\CancellableFutureInterface $future) use(&$runCount) {
    echo "time[$runCount] $time\n";
    $runCount++;
    if ($runCount === 5) {
        $future->cancel();
    }
});



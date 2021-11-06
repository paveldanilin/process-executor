<?php

use Paveldanilin\ProcessExecutor\ProcessExecutors;

require '../vendor/autoload.php';

$executor = ProcessExecutors::newScheduledPoolExecutor(4);
$runCount = 0;

$executor->schedule(1, function () {
    \sleep(5);
    return \time();
})->onFulfilled(function ($time) use(&$runCount, $executor) {
    echo "time-1[$runCount] $time\n";
    $runCount++;
    if ($runCount === 5) {
        $executor->stop();
    }
});

$executor->start();

<?php

require '../../vendor/autoload.php';

$executor = \Paveldanilin\ProcessExecutor\ProcessExecutors::newScheduledPoolExecutor(10, null);

$executor->schedule(5, function () {
    return \time();
}, function ($time) {
    echo "time $time\n";
});

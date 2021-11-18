<?php

require '../vendor/autoload.php';


\Paveldanilin\ProcessExecutor\ProcessExecutors::setLogger(new \Paveldanilin\ProcessExecutor\Log\Logger('./executor.log'));
$executor = \Paveldanilin\ProcessExecutor\ProcessExecutors::newScheduledPoolExecutor(4);

// Run job hourly
$executor->cron('* * * * *', function () {
    \file_put_contents('./cron.out', (new \DateTime())->format('H:i:s') . "\n", FILE_APPEND);
});

$executor->cron('* * * * *', function () {
    \sleep(\random_int(10, 70));
});

$executor->start();


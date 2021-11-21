<?php

use Paveldanilin\ProcessExecutor\Log\StreamLogger;
use Paveldanilin\ProcessExecutor\ProcessExecutors;
use Psr\Log\LogLevel;

require '../vendor/autoload.php';


ProcessExecutors::setLogger(
    (new StreamLogger(STDOUT))->setLevel(LogLevel::DEBUG)
);
$executor = ProcessExecutors::newScheduledPoolExecutor(4);



$executor->cron('* * * * *', function () {
    \file_put_contents('./cron.out', (new \DateTime())->format('H:i:s') . "\n", FILE_APPEND);
});

$executor->cron('* * * * *', function () {
    \sleep(\random_int(10, 70));
});

$executor->start();


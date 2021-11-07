<?php

require '../vendor/autoload.php';


$executor = \Paveldanilin\ProcessExecutor\ProcessExecutors::newScheduledPoolExecutor(4);

// Run job hourly
$executor->cron('0 * * * *', function () {
    \file_put_contents('./cron.out', (new \DateTime())->format('H:i:s') . "\n", FILE_APPEND);
});

$executor->start();


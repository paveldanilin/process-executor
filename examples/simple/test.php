<?php

use Paveldanilin\ProcessExecutor\ProcessExecutors;

require '../../vendor/autoload.php';

$executor = ProcessExecutors::newFixedPoolExecutor(1);

$executor->submit(fn() => 1)->then(function ($data) {
    print $data . "\n";
    \React\EventLoop\Loop::stop();
});


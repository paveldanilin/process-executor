<?php

use Paveldanilin\ProcessExecutor\ProcessExecutors;

require '../vendor/autoload.php';

ProcessExecutors::setLogger((new \Paveldanilin\ProcessExecutor\Log\StreamLogger(STDOUT))->setLevel('debug'));

$r = ProcessExecutors::waitAll([
    function () {
        \sleep(45);
        return 45;
    },
    function () {
        throw new \RuntimeException('Critical error');
    }
]);

print_r($r);

<?php

use Paveldanilin\ProcessExecutor\ProcessExecutors;

require '../vendor/autoload.php';

$executor = ProcessExecutors::newFixedPoolExecutor(5);

$executor->submit(function () {
    sleep(2);
    return 1;
})->then(function ($data) {
    print $data . "\n";
});

$executor->submit(function () {
    sleep(10);
    return 2;
}, 2)->then(function ($data) {
    print $data . "\n";
});

$executor->submit(function () {
    sleep(5);
    return 3;
})->then(function ($data) {
    print $data . "\n";
});

$executor->waitAll();

print "---\n";

$executor->submit(function () {
    sleep(4);
    return 100;
})->then(function ($data) {
    print $data . "\n";
});

$executor->waitAll();


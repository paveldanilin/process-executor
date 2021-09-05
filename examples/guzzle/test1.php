<?php

require '../../vendor/autoload.php';

use React\EventLoop\Loop;
use GuzzleHttp\Client;

$executor = \Paveldanilin\ProcessExecutor\ProcessExecutors::newSingleExecutor();

$client = new Client([
    'base_uri' => 'https://jsonplaceholder.typicode.com',
    'timeout' => 2.0,
]);
$postId = 1;

Loop::addPeriodicTimer(2 , static function () use($executor, $client, &$postId) {
    print "Fetch: " . $postId . "\n";
    $executor->execute(function () use($client, $postId) {
        $response = $client->get('/posts/' . $postId);
        \file_put_contents('post' . $postId . '.out', $response->getBody()->getContents());
    });
    $postId++;
});


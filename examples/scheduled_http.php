<?php

require '../vendor/autoload.php';

use GuzzleHttp\Client;

$executor = \Paveldanilin\ProcessExecutor\ProcessExecutors::newScheduledPoolExecutor(1);

$client = new Client([
    'base_uri' => 'https://jsonplaceholder.typicode.com',
    'timeout' => 2.0,
]);
$postId = 1;

$executor->schedule(5, function () use($client, &$postId) {
    $response = $client->get('/posts/' . $postId);
    \file_put_contents('post' . $postId . '.out', $response->getBody()->getContents());
})->onFulfilled(function () use(&$postId) {
    print "Fetched[$postId]\n";
    $postId++;
});

$executor->start();

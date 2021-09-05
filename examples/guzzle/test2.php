<?php

require '../../vendor/autoload.php';

use React\EventLoop\Loop;
use GuzzleHttp\Client;

$executor = \Paveldanilin\ProcessExecutor\ProcessExecutors::newFixedPoolExecutor(5);

$client = new Client([
    'base_uri' => 'https://jsonplaceholder.typicode.com',
    'timeout' => 2.0,
]);
$postId = 1;

Loop::addPeriodicTimer(2 , static function () use($executor, $client, &$postId) {
    /** @var array<\React\Promise\PromiseInterface> $promises */
    $promises = [];

    for ($i = 0; $i < 5; $i++) {
        print "Going to fetch post " . $postId . "\n";
        $promises[$postId] = $executor->submit(function () use($client, $postId) {
            $response = $client->get('/posts/' . $postId);
            return $response->getBody()->getContents();
        });
        $postId++;
    }

    foreach ($promises as $id => $promise) {
        $promise->then(function ($jsonResponse) use($id) {
            echo "Data received for post " . $id . "\n";
            \file_put_contents('post' . $id . '.out', $jsonResponse);
        });
    }
});

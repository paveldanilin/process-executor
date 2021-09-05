# ProcessExecutor

Based on a brilliant [reactphp library](https://github.com/reactphp/reactphp).

Allows you to execute closure in the sub-process.

For example, we can fetch data from an external API and put the result in a file.

```php
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
```

In this example the following code will be executed in the sub-process:
```php
function () use($client, $postId) {
        $response = $client->get('/posts/' . $postId);
        \file_put_contents('post' . $postId . '.out', $response->getBody()->getContents());
    })
```

See more [examples](./examples).

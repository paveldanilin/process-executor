<?php

require '../vendor/autoload.php';

$dbConnectionString = 'sqlite:///./test.db';

if (!\file_exists('./test.db')) {
    print "Creating test users table...\n";
    $c = 0;
    $conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $dbConnectionString]);
    $conn->executeQuery('CREATE TABLE users (id int, last_name text, first_name text)');
    $conn->beginTransaction();
    for($i = 1; $i < 100000; $i++) {
        $conn->insert('users', ['id' => $i +1, 'last_name' => 'aaa', 'first_name' => 'asdasd']);
        if ($c > 1000) {
            echo "Commit...[$i]\n";
            $conn->commit();
            $conn->beginTransaction();
            $c = 0;
        }
        $c++;
    }
    $conn->commit();
    print "The users table has been create\n";
}

$executor = \Paveldanilin\ProcessExecutor\ProcessExecutors::newScheduledPoolExecutor(4);

// Create a new user every 5 seconds
$executor->schedule(5, function () use($dbConnectionString) {
    $conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $dbConnectionString]);
    $sql = "SELECT count(*) as uc FROM users";
    $stmt = $conn->prepare($sql);
    $id = $stmt->execute()->fetchAssociative()['uc'];
    $conn->insert('users', ['id' => $id + 1, 'last_name' => 'aaa', 'first_name' => 'asdasd']);
});

// Print the count of users every 10 seconds
$executor->schedule(10, function () use($dbConnectionString) {
    $conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $dbConnectionString]);
    $sql = "SELECT count(*) as uc FROM users";
    $stmt = $conn->prepare($sql);
    return $stmt->execute()->fetchAssociative()['uc'];
})->onFulfilled(function ($usersCount) {
    print "Users $usersCount\n";
});


$executor->start();

<?php

$testDbPort = getenv('HUMHUB_TEST_DB_PORT');

if ($testDbPort === false || $testDbPort === '') {
    return [];
}

// HumHub 1.18 already honours HUMHUB_TEST_DB_PORT in its core test
// configuration. Early 1.19 builds did not, so module tests provide the
// same opt-in override for portable local runs (for example MAMP on 8889).
$testDbName = getenv('HUMHUB_TEST_DB_NAME') ?: 'humhub_test';

return [
    'components' => [
        'db' => [
            'dsn' => 'mysql:host=127.0.0.1;port=' . (int)$testDbPort . ';dbname=' . $testDbName,
            'username' => getenv('HUMHUB_TEST_DB_USER') ?: 'root',
            'password' => getenv('HUMHUB_TEST_DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
        ],
    ],
];

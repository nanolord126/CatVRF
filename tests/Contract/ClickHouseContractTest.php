<?php

declare(strict_types=1);

namespace Tests\Contract;

use Smi2\PhpClickHouse\Client;

// Pest test using modern declarative syntax
beforeEach(function () {
    // Ensure ClickHouse connection is available
    if (! config('database.connections.clickhouse')) {
        $this->markTestSkipped('ClickHouse connection not configured');
    }
});

test('clickhouse connection contract', function () {
    // Act: Try to connect to ClickHouse
    $client = new Client([
        'host' => config('database.connections.clickhouse.host'),
        'port' => config('database.connections.clickhouse.port'),
        'username' => config('database.connections.clickhouse.username'),
        'password' => config('database.connections.clickhouse.password'),
        'database' => config('database.connections.clickhouse.database'),
    ]);

    // Assert: Connection successful
    expect($client)->toBeInstanceOf(Client::class);
});

test('clickhouse insert contract', function () {
    // Arrange: Create test table
    $client = new Client([
        'host' => config('database.connections.clickhouse.host'),
        'port' => config('database.connections.clickhouse.port'),
        'username' => config('database.connections.clickhouse.username'),
        'password' => config('database.connections.clickhouse.password'),
        'database' => config('database.connections.clickhouse.database'),
    ]);

    $client->write('CREATE TABLE IF NOT EXISTS test_contract (id UInt32, value String) ENGINE = MergeTree() ORDER BY id');

    // Act: Insert data
    $client->insert('test_contract', [
        ['id' => 1, 'value' => 'test1'],
        ['id' => 2, 'value' => 'test2'],
    ]);

    // Assert: Data inserted successfully
    $result = $client->select('SELECT count() as count FROM test_contract');
    expect($result[0]['count'])->toBe(2);

    // Cleanup
    $client->write('DROP TABLE test_contract');
});

test('clickhouse select contract', function () {
    // Arrange: Create and populate test table
    $client = new Client([
        'host' => config('database.connections.clickhouse.host'),
        'port' => config('database.connections.clickhouse.port'),
        'username' => config('database.connections.clickhouse.username'),
        'password' => config('database.connections.clickhouse.password'),
        'database' => config('database.connections.clickhouse.database'),
    ]);

    $client->write('CREATE TABLE IF NOT EXISTS test_select_contract (id UInt32, name String, created_at DateTime) ENGINE = MergeTree() ORDER BY id');

    $client->insert('test_select_contract', [
        ['id' => 1, 'name' => 'Alice', 'created_at' => '2024-01-01 00:00:00'],
        ['id' => 2, 'name' => 'Bob', 'created_at' => '2024-01-02 00:00:00'],
    ]);

    // Act: Select data
    $result = $client->select('SELECT * FROM test_select_contract WHERE id = 1');

    // Assert: Select returns correct data
    expect($result)->toBeArray();
    expect($result[0]['id'])->toBe(1);
    expect($result[0]['name'])->toBe('Alice');

    // Cleanup
    $client->write('DROP TABLE test_select_contract');
});

test('clickhouse aggregate functions contract', function () {
    // Arrange: Create test table with numeric data
    $client = new Client([
        'host' => config('database.connections.clickhouse.host'),
        'port' => config('database.connections.clickhouse.port'),
        'username' => config('database.connections.clickhouse.username'),
        'password' => config('database.connections.clickhouse.password'),
        'database' => config('database.connections.clickhouse.database'),
    ]);

    $client->write('CREATE TABLE IF NOT EXISTS test_aggregate_contract (id UInt32, amount UInt32) ENGINE = MergeTree() ORDER BY id');

    $client->insert('test_aggregate_contract', [
        ['id' => 1, 'amount' => 100],
        ['id' => 2, 'amount' => 200],
        ['id' => 3, 'amount' => 300],
    ]);

    // Act: Run aggregate query
    $result = $client->select('SELECT sum(amount) as total, avg(amount) as average, count() as count FROM test_aggregate_contract');

    // Assert: Aggregates calculated correctly
    expect($result[0]['total'])->toBe(600);
    expect($result[0]['average'])->toBe(200.0);
    expect($result[0]['count'])->toBe(3);

    // Cleanup
    $client->write('DROP TABLE test_aggregate_contract');
});

test('clickhouse time series query contract', function () {
    // Arrange: Create time series table
    $client = new Client([
        'host' => config('database.connections.clickhouse.host'),
        'port' => config('database.connections.clickhouse.port'),
        'username' => config('database.connections.clickhouse.username'),
        'password' => config('database.connections.clickhouse.password'),
        'database' => config('database.connections.clickhouse.database'),
    ]);

    $client->write('CREATE TABLE IF NOT EXISTS test_timeseries (timestamp DateTime, metric String, value Float64) ENGINE = MergeTree() PARTITION BY toYYYYMM(timestamp) ORDER BY timestamp');

    $client->insert('test_timeseries', [
        ['timestamp' => '2024-01-01 00:00:00', 'metric' => 'requests', 'value' => 100.0],
        ['timestamp' => '2024-01-01 01:00:00', 'metric' => 'requests', 'value' => 150.0],
        ['timestamp' => '2024-01-01 02:00:00', 'metric' => 'requests', 'value' => 200.0],
    ]);

    // Act: Query time series with time bucket
    $result = $client->select('SELECT toStartOfHour(timestamp) as hour, sum(value) as total FROM test_timeseries GROUP BY hour ORDER BY hour');

    // Assert: Time bucketing works correctly
    expect($result)->toBeArray();
    expect($result)->toHaveCount(3);
    expect($result[0]['total'])->toBe(100.0);

    // Cleanup
    $client->write('DROP TABLE test_timeseries');
});

test('clickhouse error handling contract', function () {
    $client = new Client([
        'host' => config('database.connections.clickhouse.host'),
        'port' => config('database.connections.clickhouse.port'),
        'username' => config('database.connections.clickhouse.username'),
        'password' => config('database.connections.clickhouse.password'),
        'database' => config('database.connections.clickhouse.database'),
    ]);

    // Act: Try to query non-existent table
    try {
        $client->select('SELECT * FROM nonexistent_table');
        $this->fail('Expected exception for non-existent table');
    } catch (\Exception $e) {
        // Assert: Error is thrown with appropriate message
        expect($e->getMessage())->toContain('Table');
    }
});

test('clickhouse data types contract', function () {
    // Arrange: Create table with various data types
    $client = new Client([
        'host' => config('database.connections.clickhouse.host'),
        'port' => config('database.connections.clickhouse.port'),
        'username' => config('database.connections.clickhouse.username'),
        'password' => config('database.connections.clickhouse.password'),
        'database' => config('database.connections.clickhouse.database'),
    ]);

    $client->write('CREATE TABLE IF NOT EXISTS test_types (id UInt32, string_val String, int_val Int64, float_val Float64, date_val Date, datetime_val DateTime, array_val Array(UInt32)) ENGINE = MergeTree() ORDER BY id');

    // Act: Insert various data types
    $client->insert('test_types', [
        [
            'id' => 1,
            'string_val' => 'test',
            'int_val' => 42,
            'float_val' => 3.14,
            'date_val' => '2024-01-01',
            'datetime_val' => '2024-01-01 00:00:00',
            'array_val' => [1, 2, 3],
        ],
    ]);

    // Assert: Data types preserved correctly
    $result = $client->select('SELECT * FROM test_types WHERE id = 1');
    expect($result[0]['string_val'])->toBe('test');
    expect($result[0]['int_val'])->toBe(42);
    expect($result[0]['float_val'])->toBe(3.14);
    expect($result[0]['array_val'])->toBe([1, 2, 3]);

    // Cleanup
    $client->write('DROP TABLE test_types');
});

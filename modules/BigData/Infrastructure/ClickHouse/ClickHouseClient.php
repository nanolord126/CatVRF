<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\ClickHouse;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * ClickHouse Client for Big Data Operations
 *
 * Low-level ClickHouse client using HTTP interface (no PDO driver required).
 * Handles connection pooling, retries, and query execution.
 * Uses Laravel's HTTP client (Guzzle) for HTTP transport.
 */
final class ClickHouseClient
{
    private string $baseUrl;
    private int $maxRetries;
    private int $retryDelayMs;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
        private readonly int $connectTimeout = 5,
        private readonly int $queryTimeout = 30,
    ) {
        $this->baseUrl = "http://{$this->host}:{$this->port}";
        $this->maxRetries = 3;
        $this->retryDelayMs = 100;
    }

    /**
     * Execute a SELECT query and return results as array of associative arrays
     *
     * @return array<array<string, mixed>>
     */
    public function select(string $query, array $params = []): array
    {
        $result = $this->execute($query . ' FORMAT JSON', $params);

        if (isset($result['data']) && is_array($result['data'])) {
            return $result['data'];
        }

        return [];
    }

    /**
     * Execute a SELECT query and return first row
     *
     * @return array<string, mixed>|null
     */
    public function selectOne(string $query, array $params = []): ?array
    {
        $results = $this->select($query, $params);

        return $results[0] ?? null;
    }

    /**
     * Execute an INSERT query
     */
    public function insert(string $table, array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $columns = array_keys($data[0]);
        $values = [];

        foreach ($data as $row) {
            $escaped = array_map([$this, 'escapeValue'], $row);
            $values[] = '(' . implode(', ', $escaped) . ')';
        }

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $table,
            implode(', ', $columns),
            implode(', ', $values)
        );

        $this->execute($query);

        return count($data);
    }

    /**
     * Execute an INSERT query using ClickHouse format (JSONEachRow, CSV, etc.)
     */
    public function insertFormat(string $table, string $format, string $data): int
    {
        $query = "INSERT INTO {$table} FORMAT {$format}";
        $url = $this->buildUrl($query);

        $this->sendRequest($url, 'POST', $data);

        return 1;
    }

    /**
     * Insert data as JSONEachRow format (most efficient for batch inserts)
     */
    public function insertJsonEachRow(string $table, array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $query = "INSERT INTO {$table} FORMAT JSONEachRow";
        $url = $this->buildUrl($query);

        $body = '';
        foreach ($data as $row) {
            $body .= json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        }

        $this->sendRequest($url, 'POST', $body);

        return count($data);
    }

    /**
     * Execute a generic query
     *
     * @return array<string, mixed>|int
     */
    public function execute(string $query, array $params = []): array|int
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $url = $this->buildUrl($query, $params);
                $isRead = $this->isReadQuery($query);

                $response = $this->sendRequest($url, 'POST');

                if ($isRead) {
                    $json = $response->json();

                    return $json ?? [];
                }

                return $response->successful() ? 1 : 0;
            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $this->maxRetries) {
                    Log::error('ClickHouse query failed after retries', [
                        'query' => $query,
                        'error' => $e->getMessage(),
                        'attempt' => $attempt,
                    ]);

                    throw new \RuntimeException('ClickHouse query failed: ' . $e->getMessage(), 0, $e);
                }

                usleep($this->retryDelayMs * 1000 * $attempt);
            }
        }

        throw new \RuntimeException('Unexpected error in ClickHouse query execution');
    }

    /**
     * Check if connection is alive
     */
    public function ping(): bool
    {
        try {
            $response = Http::withOptions([
                'timeout' => $this->connectTimeout,
            ])->get("{$this->baseUrl}/ping");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get ClickHouse server version
     */
    public function getVersion(): string
    {
        $result = $this->selectOne('SELECT version() AS version');

        return $result['version'] ?? 'unknown';
    }

    /**
     * Get table row count
     */
    public function countRows(string $table, ?string $where = null): int
    {
        $query = "SELECT COUNT(*) AS count FROM {$table}";

        if ($where) {
            $query .= " WHERE {$where}";
        }

        $result = $this->selectOne($query);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Truncate a table
     */
    public function truncate(string $table): void
    {
        $this->execute("TRUNCATE TABLE {$table}");
    }

    /**
     * Drop a table
     */
    public function dropTable(string $table): void
    {
        $this->execute("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Get health status
     */
    public function healthCheck(): array
    {
        try {
            $version = $this->getVersion();
            $uptime = $this->selectOne('SELECT uptime() AS uptime');

            return [
                'status' => 'healthy',
                'version' => $version,
                'uptime' => $uptime['uptime'] ?? 0,
                'database' => $this->database,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'database' => $this->database,
            ];
        }
    }

    /**
     * Build URL with query and params
     */
    private function buildUrl(string $query, array $params = []): string
    {
        $queryParams = array_merge([
            'database' => $this->database,
            'query' => $query,
            'default_format' => 'JSON',
            'max_execution_time' => $this->queryTimeout,
        ], $params);

        return $this->baseUrl . '/?' . http_build_query($queryParams);
    }

    /**
     * Send HTTP request to ClickHouse
     */
    private function sendRequest(string $url, string $method = 'GET', ?string $body = null): \Illuminate\Http\Client\Response
    {
        $options = [
            'timeout' => $this->queryTimeout,
            'connect_timeout' => $this->connectTimeout,
        ];

        $request = Http::withOptions($options);

        if ($this->username) {
            $request = $request->withBasicAuth($this->username, $this->password);
        }

        if ($method === 'POST' && $body !== null) {
            $response = $request->withBody($body, 'text/plain')->post($url);
        } else {
            $response = $request->$method($url);
        }

        if ($response->failed()) {
            throw new \RuntimeException('ClickHouse HTTP error: ' . $response->body());
        }

        return $response;
    }

    /**
     * Check if query is a read query
     */
    private function isReadQuery(string $query): bool
    {
        $trimmed = strtoupper(trim($query));

        return str_starts_with($trimmed, 'SELECT')
            || str_starts_with($trimmed, 'SHOW')
            || str_starts_with($trimmed, 'DESCRIBE')
            || str_starts_with($trimmed, 'EXISTS');
    }

    /**
     * Escape a value for ClickHouse SQL
     */
    private function escapeValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }

        if (is_array($value)) {
            $escaped = array_map([$this, 'escapeValue'], $value);
            return '[' . implode(', ', $escaped) . ']';
        }

        return "'" . addslashes((string) $value) . "'";
    }
}

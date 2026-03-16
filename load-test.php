#!/usr/bin/env php
declare(strict_types=1);

/**
 * Load Testing Script
 * 
 * Simulates heavy concurrent load on the application
 * Usage: php load-test.php
 */

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class LoadTester
{
    private Client $client;
    private string $baseUrl;
    private int $totalRequests = 1000;
    private int $concurrentRequests = 50;
    private array $results = [];
    private float $startTime;

    public function __construct(string $baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 5,
        ]);
        $this->startTime = microtime(true);
    }

    public function run(): void
    {
        echo "🚀 Starting Load Test\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo "Total Requests: {$this->totalRequests}\n";
        echo "Concurrent Requests: {$this->concurrentRequests}\n";
        echo str_repeat('-', 80) . "\n\n";

        // Test 1: GET /api/concerts
        $this->testGetConcerts();

        // Test 2: Search with filters
        $this->testSearch();

        // Test 3: POST /api/concerts (create)
        $this->testCreateConcerts();

        // Test 4: Mixed operations
        $this->testMixedOperations();

        // Print results
        $this->printResults();
    }

    private function testGetConcerts(): void
    {
        echo "📊 Test 1: GET /api/concerts (Read-heavy)\n";
        
        $requests = [];
        for ($i = 0; $i < $this->totalRequests; $i++) {
            $requests[] = new Request('GET', "{$this->baseUrl}/api/concerts?page=1&per_page=20");
        }

        $this->executeRequests($requests, 'get_concerts');
    }

    private function testSearch(): void
    {
        echo "📊 Test 2: Search with Filters\n";

        $keywords = ['jazz', 'rock', 'pop', 'classical', 'electronic'];
        $requests = [];

        for ($i = 0; $i < $this->totalRequests; $i++) {
            $keyword = $keywords[$i % count($keywords)];
            $requests[] = new Request(
                'GET',
                "{$this->baseUrl}/api/concerts?search={$keyword}&sort=date"
            );
        }

        $this->executeRequests($requests, 'search');
    }

    private function testCreateConcerts(): void
    {
        echo "📊 Test 3: POST /api/concerts (Write-heavy)\n";

        $requests = [];
        $token = $this->getAuthToken();

        for ($i = 0; $i < 100; $i++) {
            $body = json_encode([
                'name' => "Load Test Concert {$i}",
                'venue' => 'Test Hall ' . $i,
                'date' => date('Y-m-d', strtotime("+{$i} days")),
                'time' => '20:00',
                'capacity' => 500 + ($i % 200),
                'price' => 50.00 + ($i % 100),
                'description' => 'Load test concert',
            ]);

            $req = new Request('POST', "{$this->baseUrl}/api/concerts");
            $req = $req->withHeader('Content-Type', 'application/json')
                ->withHeader('Authorization', "Bearer {$token}");
            
            $requests[] = $req;
        }

        $this->executeRequests($requests, 'create_concerts');
    }

    private function testMixedOperations(): void
    {
        echo "📊 Test 4: Mixed Operations (Realistic)\n";

        $requests = [];
        $token = $this->getAuthToken();

        for ($i = 0; $i < $this->totalRequests; $i++) {
            $op = $i % 3;

            if ($op === 0) {
                // Read
                $req = new Request('GET', "{$this->baseUrl}/api/concerts?page=" . ($i % 10 + 1));
            } elseif ($op === 1) {
                // Search
                $keyword = ['jazz', 'rock', 'pop'][$i % 3];
                $req = new Request('GET', "{$this->baseUrl}/api/concerts?search={$keyword}");
            } else {
                // Create (10% of operations)
                $body = json_encode([
                    'name' => "Mixed Test {$i}",
                    'venue' => "Hall {$i}",
                    'date' => date('Y-m-d', strtotime("+{$i} days")),
                    'time' => '20:00',
                    'capacity' => 500,
                    'price' => 50.00,
                ]);

                $req = new Request('POST', "{$this->baseUrl}/api/concerts");
                $req = $req->withHeader('Content-Type', 'application/json')
                    ->withHeader('Authorization', "Bearer {$token}");
            }

            $requests[] = $req;
        }

        $this->executeRequests($requests, 'mixed_operations');
    }

    private function executeRequests(array $requests, string $label): void
    {
        $startTime = microtime(true);
        $successful = 0;
        $failed = 0;
        $responseTimes = [];

        $pool = new Pool($this->client, $requests, [
            'concurrency' => $this->concurrentRequests,
            'fulfilled' => function ($response) use (&$successful, &$responseTimes) {
                $successful++;
                $responseTimes[] = (float) $response->getHeaderLine('X-Response-Time') ?? 0;
            },
            'rejected' => function ($reason) use (&$failed) {
                $failed++;
                echo "❌ Error: {$reason}\n";
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        $duration = microtime(true) - $startTime;

        // Store results
        $this->results[$label] = [
            'duration' => $duration,
            'successful' => $successful,
            'failed' => $failed,
            'total' => $successful + $failed,
            'avg_response_time' => $responseTimes ? array_sum($responseTimes) / count($responseTimes) : 0,
            'min_response_time' => $responseTimes ? min($responseTimes) : 0,
            'max_response_time' => $responseTimes ? max($responseTimes) : 0,
            'requests_per_second' => $successful / $duration,
        ];

        echo "✅ Completed in {$duration:.2f}s\n";
        echo "   Successful: {$successful} | Failed: {$failed}\n";
        echo "   Requests/sec: " . number_format($this->results[$label]['requests_per_second'], 2) . "\n";
        echo "   Avg response: " . number_format($this->results[$label]['avg_response_time'], 2) . "ms\n\n";
    }

    private function printResults(): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "📈 LOAD TEST RESULTS\n";
        echo str_repeat('=', 80) . "\n\n";

        $totalTime = microtime(true) - $this->startTime;
        $totalRequests = array_sum(array_column($this->results, 'total'));
        $totalSuccessful = array_sum(array_column($this->results, 'successful'));

        echo "Overall Statistics:\n";
        echo "  Total Time: " . number_format($totalTime, 2) . "s\n";
        echo "  Total Requests: {$totalRequests}\n";
        echo "  Successful: {$totalSuccessful}\n";
        echo "  Overall RPS: " . number_format($totalRequests / $totalTime, 2) . "\n\n";

        echo "Test Results:\n";
        echo str_repeat('-', 80) . "\n";

        foreach ($this->results as $testName => $result) {
            echo "\n{$testName}:\n";
            echo "  Duration: {$result['duration']:.2f}s\n";
            echo "  Requests: {$result['successful']}/{$result['total']}\n";
            echo "  RPS: " . number_format($result['requests_per_second'], 2) . "\n";
            echo "  Avg Response: " . number_format($result['avg_response_time'], 2) . "ms\n";
            echo "  Min/Max: {$result['min_response_time']:.2f}ms / {$result['max_response_time']:.2f}ms\n";
        }

        echo "\n" . str_repeat('=', 80) . "\n";

        // Performance assessment
        $avgRps = $totalRequests / $totalTime;
        if ($avgRps > 100) {
            echo "✅ EXCELLENT: {$avgRps:.0f} requests/second\n";
        } elseif ($avgRps > 50) {
            echo "⚠️  GOOD: {$avgRps:.0f} requests/second\n";
        } else {
            echo "❌ POOR: {$avgRps:.0f} requests/second\n";
        }

        echo str_repeat('=', 80) . "\n";
    }

    private function getAuthToken(): string
    {
        $response = $this->client->post("{$this->baseUrl}/api/login", [
            'json' => [
                'email' => 'admin@catvrf.local',
                'password' => 'password123',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['token'] ?? '';
    }
}

// Run load test
$tester = new LoadTester($_ENV['APP_URL'] ?? 'http://localhost:8000');
$tester->run();

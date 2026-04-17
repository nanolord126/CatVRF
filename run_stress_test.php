<?php

declare(strict_types=1);

/**
 * Autonomous Stress Test Script for Beauty Vertical
 * Runs stress tests without requiring Laravel HTTP server
 */

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class BeautyStressTest
{
    private int $requestsSent = 0;
    private int $requestsFailed = 0;
    private float $startTime;
    private bool $running = true;
    private string $testUrl;

    public function __construct(string $testUrl = 'http://localhost:8001/stress-test')
    {
        $this->testUrl = $testUrl;
    }

    public function run(string $type = 'all', int $concurrent = 100, int $duration = 60, bool $crash = false): void
    {
        $this->startTime = microtime(true);

        if ($crash) {
            $concurrent *= 10;
            $duration *= 2;
            echo "CRASH MODE ENABLED: {$concurrent} concurrent requests for {$duration}s\n";
        }

        echo "Starting Beauty stress test...\n";
        echo "Type: {$type}\n";
        echo "Concurrent: {$concurrent}\n";
        echo "Duration: {$duration}s\n";
        echo "URL: {$this->testUrl}\n\n";

        switch ($type) {
            case 'spam':
                $this->runSpamAttack($concurrent, $duration);
                break;
            case 'ddos':
                $this->runDdosAttack($concurrent, $duration);
                break;
            case 'fraud':
                $this->runFraudAttack($concurrent, $duration);
                break;
            case 'queues':
                $this->runQueueOverload($concurrent, $duration);
                break;
            case 'all':
                $segmentDuration = (int)($duration / 4);
                $this->runSpamAttack($concurrent, $segmentDuration);
                $this->newLine();
                $this->runDdosAttack($concurrent, $segmentDuration);
                $this->newLine();
                $this->runFraudAttack($concurrent, $segmentDuration);
                $this->newLine();
                $this->runQueueOverload($concurrent, $segmentDuration);
                break;
        }

        $this->printResults();
    }

    private function runSpamAttack(int $concurrent, int $duration): void
    {
        echo "Running SPAM attack simulation...\n";
        $endTime = microtime(true) + $duration;

        while (microtime(true) < $endTime && $this->running) {
            $promises = [];
            for ($i = 0; $i < $concurrent; $i++) {
                $promises[] = $this->sendSpamRequest();
            }

            foreach ($promises as $success) {
                if (!$success) {
                    $this->requestsFailed++;
                }
                $this->requestsSent++;
            }

            $this->printProgress();
            usleep(100000); // 100ms between batches
        }
    }

    private function runDdosAttack(int $concurrent, int $duration): void
    {
        echo "Running DDoS attack simulation...\n";
        $endTime = microtime(true) + $duration;

        for ($i = 0; $i < $concurrent && microtime(true) < $endTime && $this->running; $i++) {
            $success = $this->sendDdosRequest($i);
            if (!$success) {
                $this->requestsFailed++;
            }
            $this->requestsSent++;
            $this->printProgress();
        }
    }

    private function runFraudAttack(int $concurrent, int $duration): void
    {
        echo "Running FRAUD attack simulation...\n";
        $endTime = microtime(true) + $duration;
        $patterns = ['same_ip', 'rapid_actions', 'unusual_amounts', 'suspicious_user_agents'];

        while (microtime(true) < $endTime && $this->running) {
            for ($i = 0; $i < $concurrent; $i++) {
                $pattern = $patterns[$i % count($patterns)];
                $success = $this->sendFraudRequest($i, $pattern);
                if (!$success) {
                    $this->requestsFailed++;
                }
                $this->requestsSent++;
            }
            $this->printProgress();
            usleep(100000);
        }
    }

    private function runQueueOverload(int $concurrent, int $duration): void
    {
        echo "Running QUEUE overload simulation...\n";
        $endTime = microtime(true) + $duration;

        while (microtime(true) < $endTime && $this->running) {
            for ($i = 0; $i < $concurrent; $i++) {
                try {
                    Queue::push(new class {
                        public function handle() {
                            usleep(10000); // Simulate work
                        }
                    });
                    $this->requestsSent++;
                } catch (\Exception $e) {
                    $this->requestsFailed++;
                }
            }
            $this->printProgress();
            usleep(100000);
        }
    }

    private function sendSpamRequest(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->testUrl);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendDdosRequest(int $index): bool
    {
        try {
            $response = Http::timeout(2)->get($this->testUrl);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendFraudRequest(int $index, string $pattern): bool
    {
        try {
            $response = Http::timeout(3)->get($this->testUrl);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function printProgress(): void
    {
        echo "\rRequests: {$this->requestsSent} | Failed: {$this->requestsFailed}";
    }

    private function printResults(): void
    {
        $totalTime = microtime(true) - $this->startTime;
        $successRate = $this->requestsSent > 0 
            ? round((($this->requestsSent - $this->requestsFailed) / $this->requestsSent) * 100, 2) 
            : 0;
        $rps = $totalTime > 0 ? round($this->requestsSent / $totalTime, 2) : 0;

        echo "\n\n=== RESULTS ===\n";
        echo "Requests sent: {$this->requestsSent}\n";
        echo "Requests failed: {$this->requestsFailed}\n";
        echo "Success rate: {$successRate}%\n";
        echo "RPS: {$rps}\n";
        echo "Total time: " . round($totalTime, 2) . "s\n";
    }

    private function newLine(): void
    {
        echo "\n";
    }
}

// Run stress test from command line arguments
$type = $argv[1] ?? 'all';
$concurrent = (int)($argv[2] ?? 100);
$duration = (int)($argv[3] ?? 60);
$crash = isset($argv[4]) && $argv[4] === '--crash';

$test = new BeautyStressTest();
$test->run($type, $concurrent, $duration, $crash);

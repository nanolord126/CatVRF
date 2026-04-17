<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

final class StressTestBeauty extends Command
{
    protected $signature = 'beauty:stress-test 
                            {--type=all : Test type (all, spam, ddos, fraud, queues)}
                            {--concurrent=100 : Concurrent requests}
                            {--duration=60 : Test duration in seconds}
                            {--crash : Attempt to crash system}';

    protected $description = 'Stress test Beauty vertical with spam, DDoS, fraud, and queue overload';

    private int $requestsSent = 0;
    private int $requestsFailed = 0;
    private float $startTime;
    private bool $running = true;
    private string $baseUrl = 'http://localhost:8001';

    public function handle(): int
    {
        $this->startTime = microtime(true);
        $type = $this->option('type');
        $concurrent = (int) $this->option('concurrent');
        $duration = (int) $this->option('duration');
        $crash = $this->option('crash');

        $this->info("Starting Beauty stress test...");
        $this->info("Type: {$type}");
        $this->info("Concurrent: {$concurrent}");
        $this->info("Duration: {$duration}s");
        $this->info("Crash mode: " . ($crash ? 'YES' : 'NO'));

        if ($crash) {
            $concurrent *= 10;
            $duration *= 2;
            $this->warn("CRASH MODE ENABLED: {$concurrent} concurrent requests for {$duration}s");
        }

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
            default:
                $this->runAllAttacks($concurrent, $duration);
                break;
        }

        $totalTime = microtime(true) - $this->startTime;
        $rps = $this->requestsSent / $totalTime;

        $this->newLine();
        $this->info("=== RESULTS ===");
        $this->info("Requests sent: {$this->requestsSent}");
        $this->info("Requests failed: {$this->requestsFailed}");
        $this->info("Success rate: " . round((($this->requestsSent - $this->requestsFailed) / $this->requestsSent) * 100, 2) . "%");
        $this->info("RPS: " . round($rps, 2));
        $this->info("Total time: " . round($totalTime, 2) . "s");

        return Command::SUCCESS;
    }

    private function runSpamAttack(int $concurrent, int $duration): void
    {
        $this->info("Running SPAM attack simulation...");

        $endTime = time() + $duration;

        while (time() < $endTime && $this->running) {
            for ($i = 0; $i < $concurrent; $i++) {
                $result = $this->sendSpamRequest();
                $this->requestsSent++;
                if (!$result) {
                    $this->requestsFailed++;
                }
            }

            $this->output->write("\rRequests: {$this->requestsSent} | Failed: {$this->requestsFailed}");
            usleep(10000);
        }
    }

    private function runDdosAttack(int $concurrent, int $duration): void
    {
        $this->info("Running DDoS attack simulation...");

        $endTime = time() + $duration;

        while (time() < $endTime && $this->running) {
            for ($i = 0; $i < $concurrent; $i++) {
                $result = $this->sendDdosRequest($i);
                $this->requestsSent++;
                if (!$result) {
                    $this->requestsFailed++;
                }
            }

            $this->output->write("\rRequests: {$this->requestsSent} | Failed: {$this->requestsFailed}");
        }
    }

    private function runFraudAttack(int $concurrent, int $duration): void
    {
        $this->info("Running FRAUD attack simulation...");

        $endTime = time() + $duration;
        $fraudPatterns = ['same_ip_spam', 'rapid_actions', 'unusual_amounts', 'suspicious_user_agents'];

        while (time() < $endTime && $this->running) {
            for ($i = 0; $i < $concurrent; $i++) {
                $pattern = $fraudPatterns[$i % count($fraudPatterns)];
                $result = $this->sendFraudRequest($pattern, $i);
                $this->requestsSent++;
                if (!$result) {
                    $this->requestsFailed++;
                }
            }

            $this->output->write("\rRequests: {$this->requestsSent} | Failed: {$this->requestsFailed}");
            usleep(5000);
        }
    }

    private function runQueueOverload(int $concurrent, int $duration): void
    {
        $this->info("Running QUEUE overload simulation...");

        $endTime = time() + $duration;

        while (time() < $endTime && $this->running) {
            for ($i = 0; $i < $concurrent; $i++) {
                try {
                    Queue::push(new \App\Jobs\ProcessPayment([
                        'order_id' => rand(1, 100000),
                        'amount' => rand(1000, 50000),
                        'tenant_id' => 1,
                    ]));
                    $this->requestsSent++;
                } catch (\Exception $e) {
                    $this->requestsFailed++;
                }
            }

            $this->output->write("\rJobs queued: {$this->requestsSent}");
            usleep(1000);
        }
    }

    private function runAllAttacks(int $concurrent, int $duration): void
    {
        $this->info("Running ALL attack simulations...");

        $segmentDuration = $duration / 4;

        $this->runSpamAttack($concurrent, $segmentDuration);
        $this->newLine();
        $this->runDdosAttack($concurrent, $segmentDuration);
        $this->newLine();
        $this->runFraudAttack($concurrent, $segmentDuration);
        $this->newLine();
        $this->runQueueOverload($concurrent, $segmentDuration);
    }

    private function sendSpamRequest(): bool
    {
        try {
            $response = Http::timeout(5)->post('http://localhost:8000/api/beauty/fraud/analyze', [
                'user_id' => 999,
                'action' => 'appointment_booking',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'SpamBot/1.0',
                'amount' => rand(1000, 5000),
            ], [
                'X-Tenant-ID' => '1',
                'X-Correlation-ID' => Str::uuid()->toString(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendDdosRequest(int $index): bool
    {
        try {
            $response = Http::timeout(2)->post($this->baseUrl . '/api/beauty/stress-test', [
                'user_id' => rand(1, 10000),
                'action' => 'payment',
                'ip_address' => sprintf('10.%d.%d.%d', $index % 255, rand(0, 255), rand(0, 255)),
                'user_agent' => 'DDoSBot/2.0',
                'amount' => rand(10000, 100000),
            ], [
                'X-Tenant-ID' => '1',
                'X-Correlation-ID' => Str::uuid()->toString(),
            ]);

            return $response->successful() || $response->status() === 429;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendFraudRequest(string $pattern, int $index): bool
    {
        try {
            $payload = [
                'user_id' => rand(1, 1000),
                'action' => 'payment',
                'ip_address' => '192.168.1.' . ($index % 255),
                'user_agent' => 'Mozilla/5.0',
            ];

            switch ($pattern) {
                case 'same_ip_spam':
                    $payload['user_id'] = 888;
                    $payload['ip_address'] = '192.168.1.200';
                    break;
                case 'rapid_actions':
                    $payload['action'] = ['booking', 'payment', 'cancel'][$index % 3];
                    break;
                case 'unusual_amounts':
                    $payload['amount'] = rand(50000, 200000);
                    break;
                case 'suspicious_user_agents':
                    $payload['user_agent'] = 'MaliciousBot/1.0';
                    break;
            }

            $response = Http::timeout(3)->get($this->baseUrl . '/stress-test', [
                'X-Tenant-ID' => '1',
                'X-Correlation-ID' => Str::uuid()->toString(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}

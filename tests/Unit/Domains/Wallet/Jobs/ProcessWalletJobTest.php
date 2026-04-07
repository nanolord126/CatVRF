<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Jobs;

use App\Domains\Wallet\Jobs\ProcessWalletJob;
use PHPUnit\Framework\TestCase;

final class ProcessWalletJobTest extends TestCase
{
    public function test_job_is_serializable(): void
    {
        $job = new ProcessWalletJob(modelId: 1, correlationId: 'test-corr');

        // Job should be serializable (no LoggerInterface in constructor)
        $serialized = serialize($job);
        $this->assertNotEmpty($serialized);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(ProcessWalletJob::class, $unserialized);
    }

    public function test_job_has_correct_tries(): void
    {
        $job = new ProcessWalletJob(modelId: 1, correlationId: 'test-corr');
        $this->assertSame(3, $job->tries);
    }

    public function test_job_has_correct_backoff(): void
    {
        $job = new ProcessWalletJob(modelId: 1, correlationId: 'test-corr');
        $this->assertSame(60, $job->backoff);
    }

    public function test_job_queue_is_wallet(): void
    {
        $job = new ProcessWalletJob(modelId: 1, correlationId: 'test-corr');
        $this->assertSame('wallet', $job->queue);
    }

    public function test_job_is_final_class(): void
    {
        $ref = new \ReflectionClass(ProcessWalletJob::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_job_handle_method_accepts_logger_and_audit(): void
    {
        $ref = new \ReflectionMethod(ProcessWalletJob::class, 'handle');
        $params = $ref->getParameters();

        $paramNames = array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            $params,
        );

        $this->assertContains('logger', $paramNames);
        $this->assertContains('audit', $paramNames);
    }
}

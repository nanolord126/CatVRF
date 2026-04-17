<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\Services;

use Modules\Fashion\Services\FashionReturnProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionReturnProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionReturnProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionReturnProcessingService::class);
    }

    public function test_process_return_request(): void
    {
        $result = $this->service->processReturnRequest(1, 1, 'wrong_size', 'new_with_tags', 1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_approve_return(): void
    {
        $result = $this->service->approveReturn(1, 1);

        $this->assertIsBool($result);
    }

    public function test_reject_return(): void
    {
        $result = $this->service->rejectReturn(1, 'item used', 1);

        $this->assertIsBool($result);
    }

    public function test_process_refund(): void
    {
        $result = $this->service->processRefund(1, 100.50, 1);

        $this->assertIsBool($result);
    }

    public function test_get_return_statistics(): void
    {
        $result = $this->service->getReturnStatistics(1, 1, '30d');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_returns', $result);
        $this->assertArrayHasKey('approval_rate', $result);
    }

    public function test_get_return_statistics_7d_period(): void
    {
        $result = $this->service->getReturnStatistics(1, 1, '7d');

        $this->assertIsArray($result);
    }

    public function test_get_return_statistics_90d_period(): void
    {
        $result = $this->service->getReturnStatistics(1, 1, '90d');

        $this->assertIsArray($result);
    }

    public function test_get_user_returns(): void
    {
        $result = $this->service->getUserReturns(1, 1);

        $this->assertIsArray($result);
    }

    public function test_return_statistics_includes_top_reasons(): void
    {
        $result = $this->service->getReturnStatistics(1, 1, '30d');

        $this->assertArrayHasKey('top_return_reasons', $result);
    }
}

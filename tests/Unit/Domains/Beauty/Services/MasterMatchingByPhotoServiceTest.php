<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\MasterMatchingByPhotoDto;
use App\Domains\Beauty\Services\MasterMatchingByPhotoService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MasterMatchingByPhotoServiceTest extends TestCase
{
    use RefreshDatabase;

    private MasterMatchingByPhotoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MasterMatchingByPhotoService::class);
    }

    public function test_match_masters_by_photo(): void
    {
        $dto = new MasterMatchingByPhotoDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            photo: base64_encode('fake_photo_data'),
            serviceType: 'haircut',
            preferredGender: 'female',
            maxDistance: 10,
            minRating: 4.0,
            priceMin: 1000,
            priceMax: 5000,
            correlationId: 'test-correlation',
        );

        $result = $this->service->matchByPhoto($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('matched_masters', $result);
        $this->assertTrue($result['success']);
    }

    public function test_fraud_check_on_match(): void
    {
        $dto = new MasterMatchingByPhotoDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            photo: base64_encode('fake_photo_data'),
            correlationId: 'test-correlation',
        );

        $this->expectException(\Exception::class);
        $this->service->matchByPhoto($dto);
    }
}

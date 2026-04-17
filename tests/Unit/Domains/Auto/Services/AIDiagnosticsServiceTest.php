<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Auto\Services;

use App\Domains\Auto\Services\AIDiagnosticsService;
use PHPUnit\Framework\TestCase;

final class AIDiagnosticsServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(AIDiagnosticsService::class);
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_has_diagnose_method(): void
    {
        $this->assertTrue(method_exists(AIDiagnosticsService::class, 'diagnoseByPhotoAndVIN'));
    }

    public function test_has_initiate_video_inspection_method(): void
    {
        $this->assertTrue(method_exists(AIDiagnosticsService::class, 'initiateVideoInspection'));
    }

    public function test_has_book_service_method(): void
    {
        $this->assertTrue(method_exists(AIDiagnosticsService::class, 'bookServiceWithSplitPayment'));
    }

    public function test_has_get_vehicle_method(): void
    {
        $this->assertTrue(method_exists(AIDiagnosticsService::class, 'getVehicleById'));
    }

    public function test_has_vin_decoding_method(): void
    {
        $this->assertTrue(method_exists(AIDiagnosticsService::class, 'decodeVIN'));
    }
}

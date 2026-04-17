<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Auto\Services;

use App\Domains\Auto\Services\CarImportService;
use PHPUnit\Framework\TestCase;

final class CarImportServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(CarImportService::class);
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_has_calculate_customs_duties_method(): void
    {
        $this->assertTrue(method_exists(CarImportService::class, 'calculateCustomsDuties'));
    }

    public function test_has_initiate_import_process_method(): void
    {
        $this->assertTrue(method_exists(CarImportService::class, 'initiateImportProcess'));
    }

    public function test_has_pay_customs_duties_method(): void
    {
        $this->assertTrue(method_exists(CarImportService::class, 'payCustomsDuties'));
    }

    public function test_customs_rates_array_exists(): void
    {
        $reflection = new \ReflectionClass(CarImportService::class);
        $constants = $reflection->getConstants();
        $this->assertArrayHasKey('CUSTOMS_RATES', $constants);
    }

    public function test_engine_rates_array_exists(): void
    {
        $reflection = new \ReflectionClass(CarImportService::class);
        $constants = $reflection->getConstants();
        $this->assertArrayHasKey('ENGINE_RATES', $constants);
    }
}

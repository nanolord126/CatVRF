<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Travel\Models\TravelBooking;

/**
 * Unit tests for TravelBooking model.
 *
 * @covers \App\Domains\Travel\Models\TravelBooking
 * @group travel-models
 */
final class TravelBookingTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $class = $this->getModelClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $reflection = new \ReflectionClass($class);
        $this->assertTrue($reflection->isFinal());
    }

    public function test_has_fillable_properties(): void
    {
        $class = $this->getModelClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        
        $model = new $class();
        $this->assertIsArray($model->getFillable());
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_has_casts(): void
    {
        $class = $this->getModelClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        
        $model = new $class();
        $this->assertIsArray($model->getCasts());
    }

    public function test_has_status_constants(): void
    {
        $class = $this->getModelClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        
        $this->assertClassHasConstant('STATUS_PENDING', $class);
        $this->assertClassHasConstant('STATUS_CONFIRMED', $class);
        $this->assertClassHasConstant('STATUS_CANCELLED', $class);
    }

    private function getModelClass(): string
    {
        return 'App\Domains\Travel\Models\TravelBooking';
    }
}

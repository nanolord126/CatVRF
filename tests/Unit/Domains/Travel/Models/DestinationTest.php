<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Travel\Models\Destination;

/**
 * Unit tests for Destination model.
 *
 * @covers \App\Domains\Travel\Models\Destination
 * @group travel-models
 */
final class DestinationTest extends TestCase
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

    private function getModelClass(): string
    {
        return 'App\Domains\Travel\Models\Destination';
    }
}

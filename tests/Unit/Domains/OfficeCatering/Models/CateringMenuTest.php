<?php declare(strict_types=1);

namespace Tests\Unit\Domains\OfficeCatering\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CateringMenu model.
 *
 * @covers \App\Domains\OfficeCatering\Models\CateringMenu
 */
final class CateringMenuTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\OfficeCatering\Models\CateringMenu::class
        );
        $this->assertTrue($reflection->isFinal(), 'CateringMenu must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\OfficeCatering\Models\CateringMenu();
        $this->assertNotEmpty($model->getFillable(), 'CateringMenu must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\OfficeCatering\Models\CateringMenu();
        $this->assertNotEmpty($model->getCasts(), 'CateringMenu must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\OfficeCatering\Models\CateringMenu();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}

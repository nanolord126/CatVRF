<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\PersonalDevelopment\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\PersonalDevelopment\Models\Course;

/**
 * Unit tests for Course model.
 *
 * @covers \App\Domains\PersonalDevelopment\Models\Course
 */
final class CourseTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            Course::class
        );
        $this->assertTrue($reflection->isFinal(), 'Course must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new Course();
        $this->assertNotEmpty($model->getFillable(), 'Course must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new Course();
        $this->assertNotEmpty($model->getCasts(), 'Course must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new Course();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}

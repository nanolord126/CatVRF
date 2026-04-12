<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Communication\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ChatRoom model.
 *
 * @covers \App\Domains\Communication\Models\ChatRoom
 */
final class ChatRoomTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Models\ChatRoom::class
        );
        $this->assertTrue($reflection->isFinal(), 'ChatRoom must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Communication\Models\ChatRoom();
        $this->assertNotEmpty($model->getFillable(), 'ChatRoom must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Communication\Models\ChatRoom();
        $this->assertNotEmpty($model->getCasts(), 'ChatRoom must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Communication\Models\ChatRoom();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}

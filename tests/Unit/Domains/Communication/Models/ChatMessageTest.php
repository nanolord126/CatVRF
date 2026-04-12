<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Communication\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ChatMessage model.
 *
 * @covers \App\Domains\Communication\Models\ChatMessage
 */
final class ChatMessageTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Models\ChatMessage::class
        );
        $this->assertTrue($reflection->isFinal(), 'ChatMessage must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Communication\Models\ChatMessage();
        $this->assertNotEmpty($model->getFillable(), 'ChatMessage must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Communication\Models\ChatMessage();
        $this->assertNotEmpty($model->getCasts(), 'ChatMessage must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Communication\Models\ChatMessage();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}

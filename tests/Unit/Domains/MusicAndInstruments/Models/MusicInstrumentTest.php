<?php declare(strict_types=1);

namespace Tests\Unit\Domains\MusicAndInstruments\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MusicInstrument model.
 *
 * @covers \App\Domains\MusicAndInstruments\Models\MusicInstrument
 */
final class MusicInstrumentTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MusicAndInstruments\Models\MusicInstrument::class
        );
        $this->assertTrue($reflection->isFinal(), 'MusicInstrument must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\MusicAndInstruments\Models\MusicInstrument();
        $this->assertNotEmpty($model->getFillable(), 'MusicInstrument must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\MusicAndInstruments\Models\MusicInstrument();
        $this->assertNotEmpty($model->getCasts(), 'MusicInstrument must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\MusicAndInstruments\Models\MusicInstrument();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}

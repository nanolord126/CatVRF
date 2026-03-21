<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Pharmacy;

use App\Domains\Pharmacy\Models\Pharmacy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class PharmacyTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_pharmacy(): void
    {
        $pharmacy = Pharmacy::factory()->create();
        $this->assertDatabaseHas('pharmacies', ['sku' => $pharmacy->sku, 'tenant_id' => 1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pharmacy_prescription_state(): void
    {
        $pharmacy = Pharmacy::factory()->prescription()->create();
        $this->assertTrue($pharmacy->requires_prescription);
        $this->assertFalse($pharmacy->is_otc);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pharmacy_otc_state(): void
    {
        $pharmacy = Pharmacy::factory()->otc()->create();
        $this->assertTrue($pharmacy->is_otc);
        $this->assertFalse($pharmacy->requires_prescription);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pharmacy_form(): void
    {
        $pharmacy = Pharmacy::factory()->create(['form' => 'tablet']);
        $this->assertEquals('tablet', $pharmacy->form);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pharmacy_dosage(): void
    {
        $pharmacy = Pharmacy::factory()->create(['dosage' => '500mg']);
        $this->assertEquals('500mg', $pharmacy->dosage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pharmacy_has_rating(): void
    {
        $pharmacy = Pharmacy::factory()->create(['rating' => 4.6]);
        $this->assertEquals(4.6, $pharmacy->rating);
    }
}

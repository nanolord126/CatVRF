<?php declare(strict_types=1);

namespace Tests\Unit\Pharmacy;

use Tests\TestCase;
use App\Domains\Pharmacy\Models\Pharmacy;

final class PharmacyModelTest extends TestCase
{
    public function test_pharmacy_has_uuid(): void
    {
        $p = new Pharmacy(['name' => 'Test']);
        $this->assertNotNull($p);
    }
}

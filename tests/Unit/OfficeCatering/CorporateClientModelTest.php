<?php declare(strict_types=1);

namespace Tests\Unit\OfficeCatering;

use Tests\TestCase;
use App\Domains\OfficeCatering\Models\CorporateClient;

final class CorporateClientModelTest extends TestCase
{
    public function test_corporate_client_model(): void
    {
        $c = new CorporateClient(['name' => 'BigCorp']);
        $this->assertNotNull($c);
    }
}

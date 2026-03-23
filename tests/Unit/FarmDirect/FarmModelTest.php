<?php declare(strict_types=1);

namespace Tests\Unit\FarmDirect;

use Tests\TestCase;
use App\Domains\FarmDirect\Models\Farm;

final class FarmModelTest extends TestCase
{
    public function test_farm_model(): void
    {
        $f = new Farm(['name' => 'GreenFarm']);
        $this->assertNotNull($f);
    }
}

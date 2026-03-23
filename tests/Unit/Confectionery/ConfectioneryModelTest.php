<?php declare(strict_types=1);

namespace Tests\Unit\Confectionery;

use Tests\TestCase;
use App\Domains\Confectionery\Models\ConfectioneryShop;

final class ConfectioneryModelTest extends TestCase
{
    public function test_confectionery_model(): void
    {
        $s = new ConfectioneryShop(['name' => 'CakeArt']);
        $this->assertNotNull($s);
    }
}

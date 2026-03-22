<?php declare(strict_types=1);

namespace Tests\Unit\Grocery;

use App\Domains\Grocery\Services\GroceryB2BService;
use App\Services\FraudControlService;
use Tests\TestCase;
use Mockery;

final class GroceryB2BServiceTest extends TestCase
{
    public function test_create_b2b_store_calls_fraud_check(): void
    {
        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')->once();

        $service = new GroceryB2BService($fraudMock);
        
        $data = [
            'tenant_id' => 1,
            'name' => 'B2B Grocery',
            'address' => 'B2B Street',
        ];

        $service->createB2BStore($data, 'correlation-123');
        $this->assertTrue(true);
    }
}

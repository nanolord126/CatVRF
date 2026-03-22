<?php declare(strict_types=1);

namespace Tests\Unit\Beauty;

use App\Domains\Beauty\Services\PortfolioService;
use App\Services\FraudControlService;
use Tests\TestCase;
use Mockery;

final class PortfolioServiceTest extends TestCase
{
    public function test_add_portfolio_item_calls_fraud_check(): void
    {
        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')->once();

        $service = new PortfolioService($fraudMock);
        
        $data = [
            'tenant_id' => 1,
            'salon_id' => 1,
            'master_id' => 1,
            'image_url' => 'http://example.com/image.jpg',
            'description' => 'Great hair cut!',
        ];

        $service->addPortfolioItem($data, 'correlation-123');
        $this->assertTrue(true);
    }
}

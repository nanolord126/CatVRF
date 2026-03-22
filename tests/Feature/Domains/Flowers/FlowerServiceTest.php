<?php declare(strict_types=1);

use App\Domains\Flowers\Models\{Bouquet, FlowerOrder};
use App\Domains\Flowers\Services\FlowerService;

beforeEach(function () {
    $this->service = app(FlowerService::class);
    $this->bouquet = Bouquet::factory()->create([
        'tenant_id' => 1,
        'price' => 1000,
    ]);
});

test('creates B2C order with correct price', function () {
    $data = [
        'bouquet_id' => $this->bouquet->id,
        'user_id' => 1,
    ];

    $result = $this->service->createOrder($data, false);

    expect($result)->toHaveKey('order')
        ->and($result)->toHaveKey('correlation_id')
        ->and($result['order']->total_price)->toBe('1000.00');

    $this->assertDatabaseHas('flower_orders', [
        'bouquet_id' => $this->bouquet->id,
        'correlation_id' => $result['correlation_id'],
    ]);
});

test('creates B2B order with discount', function () {
    $data = [
        'bouquet_id' => $this->bouquet->id,
        'inn' => '1234567890',
        'business_card_id' => 1,
    ];

    $result = $this->service->createOrder($data, true);

    expect($result['order']->total_price)->toBe('850.00')
        ->and($result['order']->inn)->toBe('1234567890');
});

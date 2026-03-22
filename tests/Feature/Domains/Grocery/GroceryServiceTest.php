<?php declare(strict_types=1);

use App\Domains\Grocery\Models\{GroceryStore, GroceryOrder};
use App\Domains\Grocery\Services\GroceryService;

beforeEach(function () {
    $this->service = app(GroceryService::class);
    $this->store = GroceryStore::factory()->create([
        'tenant_id' => 1,
    ]);
});

test('creates B2C grocery order', function () {
    $data = [
        'store_id' => $this->store->id,
        'user_id' => 1,
        'items' => [
            ['name' => 'Milk', 'price' => 100],
            ['name' => 'Bread', 'price' => 50],
        ],
        'delivery_address' => 'Moscow, Red Square 1',
        'delivery_slot' => '14:00-16:00',
    ];

    $result = $this->service->createOrder($data, false);

    expect($result)->toHaveKey('order')
        ->and($result['order']->total_price)->toBe('150.00');
});

test('creates B2B grocery order with discount', function () {
    $data = [
        'store_id' => $this->store->id,
        'inn' => '5555555555',
        'business_card_id' => 5,
        'items' => [
            ['name' => 'Coffee', 'price' => 500],
            ['name' => 'Sugar', 'price' => 100],
        ],
        'delivery_address' => 'Moscow, Office Tower',
        'delivery_slot' => '09:00-11:00',
    ];

    $result = $this->service->createOrder($data, true);

    expect($result['order']->total_price)->toBe('528.00')
        ->and($result['order']->inn)->toBe('5555555555');
});

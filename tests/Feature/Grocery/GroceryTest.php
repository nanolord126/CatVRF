<?php

declare(strict_types=1);

use App\Domains\Grocery\Models\GroceryStore;

test('grocery store can be created', function () {
    $store = GroceryStore::factory()->create();

    expect($store->id)->toBeNumeric()
        ->and($store->tenant_id)->toBe(1)
        ->and($store->uuid)->toBeString()
        ->and($store->correlation_id)->toBeString();
});

test('grocery order service exists', function () {
    $service = app(\App\Domains\Grocery\Services\GroceryOrderService::class);

    expect($service)->toBeInstanceOf(\App\Domains\Grocery\Services\GroceryOrderService::class);
});

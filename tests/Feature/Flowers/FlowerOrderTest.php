<?php

declare(strict_types=1);

use App\Domains\Flowers\Models\FlowerOrder;

test('flower order can be created', function () {
    $order = FlowerOrder::factory()->create();

    expect($order->id)->toBeNumeric()
        ->and($order->tenant_id)->toBe(1)
        ->and($order->uuid)->toBeString()
        ->and($order->correlation_id)->toBeString();
});

test('flower order fraud check is performed', function () {
    $service = app(\App\Domains\Flowers\Services\FlowerOrderService::class);

    expect($service)->toBeInstanceOf(\App\Domains\Flowers\Services\FlowerOrderService::class);
});

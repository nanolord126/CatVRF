<?php

declare(strict_types=1);

use App\Domains\Beauty\Models\CosmeticProduct;

test('cosmetic product can be created', function () {
    $product = CosmeticProduct::factory()->create();

    expect($product->id)->toBeNumeric()
        ->and($product->tenant_id)->toBe(1)
        ->and($product->uuid)->toBeString()
        ->and($product->brand)->toBeString();
});

test('cosmetic product belongs to salon', function () {
    $product = CosmeticProduct::factory()->create();

    expect($product->salon)->toBeInstanceOf(\App\Domains\Beauty\Models\BeautySalon::class);
});

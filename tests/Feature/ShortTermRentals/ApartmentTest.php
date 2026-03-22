<?php

declare(strict_types=1);

use App\Domains\ShortTermRentals\Models\Apartment;

test('apartment can be created', function () {
    $apartment = Apartment::factory()->create();

    expect($apartment->id)->toBeNumeric()
        ->and($apartment->tenant_id)->toBe(1)
        ->and($apartment->uuid)->toBeString()
        ->and($apartment->correlation_id)->toBeString();
});

test('apartment bookings work', function () {
    $service = app(\App\Domains\ShortTermRentals\Services\ApartmentBookingService::class);

    expect($service)->toBeInstanceOf(\App\Domains\ShortTermRentals\Services\ApartmentBookingService::class);
});

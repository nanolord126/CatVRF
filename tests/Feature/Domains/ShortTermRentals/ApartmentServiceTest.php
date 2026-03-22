<?php declare(strict_types=1);

use App\Domains\ShortTermRentals\Models\{Apartment, ApartmentBooking};
use App\Domains\ShortTermRentals\Services\ApartmentService;

beforeEach(function () {
    $this->service = app(ApartmentService::class);
    $this->apartment = Apartment::factory()->create([
        'tenant_id' => 1,
        'price_per_night' => 2000,
        'deposit_amount' => 5000,
    ]);
});

test('creates B2C booking with correct price', function () {
    $data = [
        'apartment_id' => $this->apartment->id,
        'user_id' => 1,
        'check_in' => now()->format('Y-m-d'),
        'check_out' => now()->addDays(3)->format('Y-m-d'),
        'guests_count' => 2,
    ];

    $result = $this->service->createBooking($data, false);

    expect($result)->toHaveKey('booking')
        ->and($result['booking']->total_price)->toBe('6000.00')
        ->and($result['booking']->deposit_held)->toBe('5000.00');
});

test('creates B2B booking with discount', function () {
    $data = [
        'apartment_id' => $this->apartment->id,
        'inn' => '9876543210',
        'business_card_id' => 2,
        'check_in' => now()->format('Y-m-d'),
        'check_out' => now()->addDays(5)->format('Y-m-d'),
        'guests_count' => 4,
    ];

    $result = $this->service->createBooking($data, true);

    expect($result['booking']->total_price)->toBe('9000.00')
        ->and($result['booking']->inn)->toBe('9876543210');
});

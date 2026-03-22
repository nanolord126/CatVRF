<?php

declare(strict_types=1);

use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Services\BeautyService;
use Database\Factories\Beauty\BeautySalonFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->beautyService = app(BeautyService::class);
});

test('can get active salons for tenant', function () {
    BeautySalonFactory::new()->count(3)->create(['tenant_id' => 1, 'status' => 'active']);
    BeautySalonFactory::new()->count(2)->create(['tenant_id' => 1, 'status' => 'inactive']);

    $salons = $this->beautyService->getSalons(1);

    expect($salons)->toHaveCount(3);
    foreach ($salons as $salon) {
        expect($salon->status)->toBe('active');
    }
});

test('can filter salons by business group for B2B', function () {
    BeautySalonFactory::new()->count(2)->create(['tenant_id' => 1, 'business_group_id' => 100]);
    BeautySalonFactory::new()->count(3)->create(['tenant_id' => 1, 'business_group_id' => 200]);

    $salons = $this->beautyService->getSalons(1, 100);

    expect($salons)->toHaveCount(2);
    foreach ($salons as $salon) {
        expect($salon->business_group_id)->toBe(100);
    }
});

test('salon has required fields', function () {
    $salon = BeautySalonFactory::new()->create();

    expect($salon)->toBeInstanceOf(BeautySalon::class)
        ->and($salon->uuid)->not()->toBeNull()
        ->and($salon->correlation_id)->not()->toBeNull()
        ->and($salon->tenant_id)->not()->toBeNull();
});

test('salon can be verified', function () {
    $salon = BeautySalonFactory::new()->verified()->create();

    expect($salon->is_verified)->toBeTrue();
});

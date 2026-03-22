<?php

declare(strict_types=1);

use Database\Factories\Beauty\BeautySalonFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can list salons via API', function () {
    BeautySalonFactory::new()->count(5)->create(['tenant_id' => 1, 'status' => 'active']);

    $response = $this->getJson('/api/beauty/salons?tenant_id=1');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(5);
});

test('salon has all required fields in API response', function () {
    $salon = BeautySalonFactory::new()->create(['tenant_id' => 1]);

    $response = $this->getJson("/api/beauty/salons/{$salon->id}?tenant_id=1");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveKeys([
        'id',
        'uuid',
        'name',
        'address',
        'rating',
        'is_verified',
    ]);
});

test('cannot access salon from different tenant', function () {
    $salon = BeautySalonFactory::new()->create(['tenant_id' => 999]);

    $response = $this->getJson("/api/beauty/salons/{$salon->id}?tenant_id=1");

    $response->assertStatus(403);
});

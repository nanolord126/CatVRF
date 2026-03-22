<?php

declare(strict_types=1);

use Database\Factories\Beauty\AppointmentFactory;
use Database\Factories\Beauty\BeautySalonFactory;
use Database\Factories\Beauty\MasterFactory;
use Database\Factories\Beauty\BeautyServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('appointment controller stores appointment successfully', function () {
    $salon = BeautySalonFactory::new()->create();
    $master = MasterFactory::new()->create(['salon_id' => $salon->id, 'tenant_id' => $salon->tenant_id]);
    $service = BeautyServiceFactory::new()->create(['salon_id' => $salon->id, 'master_id' => $master->id, 'tenant_id' => $salon->tenant_id]);

    $response = $this->postJson('/api/beauty/appointments', [
        'tenant_id' => $salon->tenant_id,
        'salon_id' => $salon->id,
        'master_id' => $master->id,
        'service_id' => $service->id,
        'client_id' => 1,
        'datetime_start' => now()->addDay()->toDateTimeString(),
        'price' => 200000,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('appointments', [
        'salon_id' => $salon->id,
        'master_id' => $master->id,
        'status' => 'pending',
    ]);
});

test('appointment controller returns correlation_id in response', function () {
    $salon = BeautySalonFactory::new()->create();
    $master = MasterFactory::new()->create(['salon_id' => $salon->id, 'tenant_id' => $salon->tenant_id]);
    $service = BeautyServiceFactory::new()->create(['salon_id' => $salon->id, 'master_id' => $master->id, 'tenant_id' => $salon->tenant_id]);

    $response = $this->postJson('/api/beauty/appointments', [
        'tenant_id' => $salon->tenant_id,
        'salon_id' => $salon->id,
        'master_id' => $master->id,
        'service_id' => $service->id,
        'client_id' => 1,
        'datetime_start' => now()->addDay()->toDateTimeString(),
        'price' => 200000,
    ]);

    $response->assertStatus(201);
    expect($response->json('correlation_id'))->not()->toBeNull();
});

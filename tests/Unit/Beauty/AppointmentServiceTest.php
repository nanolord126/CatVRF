<?php

declare(strict_types=1);

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Services\AppointmentService;
use Database\Factories\Beauty\AppointmentFactory;
use Database\Factories\Beauty\BeautySalonFactory;
use Database\Factories\Beauty\MasterFactory;
use Database\Factories\Beauty\BeautyServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->appointmentService = app(AppointmentService::class);
});

test('can book appointment successfully', function () {
    $salon = BeautySalonFactory::new()->create();
    $master = MasterFactory::new()->create(['salon_id' => $salon->id, 'tenant_id' => $salon->tenant_id]);
    $service = BeautyServiceFactory::new()->create(['salon_id' => $salon->id, 'master_id' => $master->id, 'tenant_id' => $salon->tenant_id]);

    $data = [
        'tenant_id' => $salon->tenant_id,
        'salon_id' => $salon->id,
        'master_id' => $master->id,
        'service_id' => $service->id,
        'client_id' => 1,
        'datetime_start' => now()->addDay(),
        'price' => 200000, // 2000 руб
    ];

    $appointment = $this->appointmentService->book($data);

    expect($appointment)->toBeInstanceOf(Appointment::class)
        ->and($appointment->status)->toBe('pending')
        ->and($appointment->correlation_id)->not()->toBeNull();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'pending',
        'salon_id' => $salon->id,
    ]);
});

test('can complete appointment successfully', function () {
    $appointment = AppointmentFactory::new()->create(['status' => 'confirmed']);
    $correlationId = 'test-correlation-id';

    $completed = $this->appointmentService->complete($appointment->id, $correlationId);

    expect($completed->status)->toBe('completed')
        ->and($completed->completed_at)->not()->toBeNull();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'completed',
    ]);
});

test('can cancel appointment successfully', function () {
    $appointment = AppointmentFactory::new()->create(['status' => 'pending']);
    $correlationId = 'test-correlation-id';

    $cancelled = $this->appointmentService->cancel($appointment->id, 'Client request', $correlationId);

    expect($cancelled->status)->toBe('cancelled');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'cancelled',
    ]);
});

test('appointment has correlation_id logged', function () {
    $appointment = AppointmentFactory::new()->create();
    expect($appointment->correlation_id)->not()->toBeNull();
});

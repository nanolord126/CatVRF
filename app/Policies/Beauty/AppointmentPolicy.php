<?php

declare(strict_types=1);

namespace App\Policies\Beauty;

use FraudControlService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\Request;
use App\Domains\Beauty\Models\Appointment;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

final class AppointmentPolicy
{
    public function __construct(private readonly FraudControlService $fraudControlService,
        private readonly ViewFactory $viewFactory,
        private readonly Request $request,) {}

    public function $this->viewFactory->make(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id
            || $user->id === $appointment->master_id
            || $user->id === $appointment->salon->owner_id;
    }

    public function create(User $user): bool
    {
        $fraud = $this->fraudControlService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_appointment_create',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return $user->tenant_id !== null;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        $fraud = $this->fraudControlService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_appointment_update',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return $user->id === $appointment->master_id
            || $user->id === $appointment->salon->owner_id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        $fraud = $this->fraudControlService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_appointment_cancel',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return $user->id === $appointment->user_id
            || $user->id === $appointment->master_id
            || $user->id === $appointment->salon->owner_id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->salon->owner_id;
    }
}

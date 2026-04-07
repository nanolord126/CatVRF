<?php declare(strict_types=1);

namespace App\Policies\Beauty;


use Illuminate\Http\Request;
use App\Domains\Beauty\Models\Appointment;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Auth\Access\HandlesAuthorization;

final class AppointmentPolicy
{
    public function __construct(
        private readonly Request $request,
    ) {}

    use HandlesAuthorization;

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id
            || $user->id === $appointment->master_id
            || $user->id === $appointment->salon->owner_id;
    }

    public function create(User $user): bool
    {
        $fraud = app(FraudControlService::class);
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_appointment_create',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        );

        return $user->tenant_id !== null;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        $fraud = app(FraudControlService::class);
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_appointment_update',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        );

        return $user->id === $appointment->master_id
            || $user->id === $appointment->salon->owner_id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        $fraud = app(FraudControlService::class);
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_appointment_cancel',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
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
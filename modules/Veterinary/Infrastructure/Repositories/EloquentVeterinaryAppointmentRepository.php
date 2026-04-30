<?php

declare(strict_types=1);

namespace Modules\Veterinary\Infrastructure\Repositories;

use Modules\Veterinary\Domain\Entities\VeterinaryAppointment;
use Modules\Veterinary\Domain\Repositories\VeterinaryAppointmentRepositoryInterface;
use Modules\Veterinary\Infrastructure\Models\VeterinaryAppointmentModel;

class EloquentVeterinaryAppointmentRepository implements VeterinaryAppointmentRepositoryInterface
{
    public function create(array $data): VeterinaryAppointment
    {
        $model = VeterinaryAppointmentModel::create($data);
        return $model->toDomain();
    }

    public function update(int $id, array $data): VeterinaryAppointment
    {
        $model = VeterinaryAppointmentModel::findOrFail($id);
        $model->update($data);
        return $model->fresh()->toDomain();
    }

    public function findById(int $id): ?VeterinaryAppointment
    {
        $model = VeterinaryAppointmentModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?VeterinaryAppointment
    {
        $model = VeterinaryAppointmentModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        return VeterinaryAppointmentModel::where('pet_id', $petId)
            ->orderBy('appointment_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByClientId(int $clientId): array
    {
        return VeterinaryAppointmentModel::where('client_id', $clientId)
            ->orderBy('appointment_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByClinicId(int $clinicId): array
    {
        return VeterinaryAppointmentModel::where('clinic_id', $clinicId)
            ->orderBy('appointment_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByVeterinarianId(int $veterinarianId): array
    {
        return VeterinaryAppointmentModel::where('veterinarian_id', $veterinarianId)
            ->orderBy('appointment_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findPendingByDate(\DateTimeImmutable $date): array
    {
        return VeterinaryAppointmentModel::where('status', 'pending')
            ->whereDate('appointment_at', $date->format('Y-m-d'))
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }
}

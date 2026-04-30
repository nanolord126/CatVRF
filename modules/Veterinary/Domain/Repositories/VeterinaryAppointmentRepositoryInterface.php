<?php

declare(strict_types=1);

namespace Modules\Veterinary\Domain\Repositories;

use Modules\Veterinary\Domain\Entities\VeterinaryAppointment;

interface VeterinaryAppointmentRepositoryInterface
{
    public function create(array $data): VeterinaryAppointment;

    public function update(int $id, array $data): VeterinaryAppointment;

    public function findById(int $id): ?VeterinaryAppointment;

    public function findByUuid(string $uuid): ?VeterinaryAppointment;

    public function findByPetId(int $petId): array;

    public function findByClientId(int $clientId): array;

    public function findByClinicId(int $clinicId): array;

    public function findByVeterinarianId(int $veterinarianId): array;

    public function findPendingByDate(\DateTimeImmutable $date): array;
}

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domains\Beauty\Domain\Entities\Appointment;
use App\Domains\Beauty\Domain\Enums\AppointmentStatus;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\Price;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment as EloquentAppointment;
use App\Shared\Domain\ValueObjects\ClientId;
use Carbon\CarbonImmutable;

/**
 * Class AppointmentMapper
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers
 */
final class AppointmentMapper
{
    public function toDomain(EloquentAppointment $eloquentAppointment): Appointment
    {
        return new Appointment(
            id: AppointmentId::fromString($eloquentAppointment->uuid),
            salonId: SalonId::fromString($eloquentAppointment->salon->uuid),
            masterId: MasterId::fromString($eloquentAppointment->master->uuid),
            serviceId: ServiceId::fromString($eloquentAppointment->service->uuid),
            clientId: new ClientId($eloquentAppointment->client_id),
            startAt: new CarbonImmutable($eloquentAppointment->start_at),
            endAt: new CarbonImmutable($eloquentAppointment->end_at),
            price: Price::fromCents($eloquentAppointment->price_cents),
            status: AppointmentStatus::from($eloquentAppointment->status),
            createdAt: new \DateTimeImmutable($eloquentAppointment->created_at),
            updatedAt: new \DateTimeImmutable($eloquentAppointment->updated_at),
        );
    }

    public function toEloquent(Appointment $appointment): EloquentAppointment
    {
        $eloquentAppointment = EloquentAppointment::firstOrNew(['uuid' => $appointment->id->getValue()]);
        $eloquentAppointment->uuid = $appointment->id->getValue();

        $eloquentSalon = \App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon::where('uuid', $appointment->salonId->getValue())->firstOrFail();
        $eloquentMaster = \App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster::where('uuid', $appointment->masterId->getValue())->firstOrFail();
        $eloquentService = \App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyService::where('uuid', $appointment->serviceId->getValue())->firstOrFail();

        $eloquentAppointment->salon_id = $eloquentSalon->id;
        $eloquentAppointment->master_id = $eloquentMaster->id;
        $eloquentAppointment->service_id = $eloquentService->id;
        $eloquentAppointment->client_id = $appointment->clientId->getValue();
        $eloquentAppointment->start_at = $appointment->startAt;
        $eloquentAppointment->end_at = $appointment->endAt;
        $eloquentAppointment->price_cents = $appointment->price->getAmountInCents();
        $eloquentAppointment->status = $appointment->status->value;

        return $eloquentAppointment;
    }
}

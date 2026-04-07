<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Beauty\Domain\Entities\Appointment;
use App\Domains\Beauty\Domain\Repositories\AppointmentRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers\AppointmentMapper;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment as EloquentAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class EloquentAppointmentRepository implements AppointmentRepositoryInterface
{
    public function __construct(
        private EloquentAppointment $eloquentAppointmentModel,
        private AppointmentMapper $appointmentMapper,
    ) {}

    public function findById(AppointmentId $id): ?Appointment
    {
        $eloquentAppointment = $this->eloquentAppointmentModel->where('uuid', $id->getValue())->first();
        return $eloquentAppointment ? $this->appointmentMapper->toDomain($eloquentAppointment) : null;
    }

    public function findByMasterForPeriod(MasterId $masterId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $eloquentMaster = \App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster::where('uuid', $masterId->getValue())->first();
        if (!$eloquentMaster) {
            return new Collection();
        }

        $eloquentAppointments = $this->eloquentAppointmentModel
            ->where('master_id', $eloquentMaster->id)
            ->where('start_at', '>=', $from)
            ->where('end_at', '<=', $to)
            ->get();

        return $eloquentAppointments->map(fn(EloquentAppointment $a) => $this->appointmentMapper->toDomain($a));
    }

    public function save(Appointment $appointment): void
    {
        $eloquentAppointment = $this->appointmentMapper->toEloquent($appointment);
        $eloquentAppointment->save();
    }

    public function delete(AppointmentId $id): bool
    {
        return (bool)$this->eloquentAppointmentModel->where('uuid', $id->getValue())->delete();
    }

    public function nextIdentity(): AppointmentId
    {
        return AppointmentId::generate();
    }
}

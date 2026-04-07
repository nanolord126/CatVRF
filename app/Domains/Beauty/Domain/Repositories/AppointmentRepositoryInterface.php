<?php

declare(strict_types=1);

/**
 * AppointmentRepositoryInterface — CatVRF 2026.
 *
 * Порт доступа к хранилищу записей (appointments).
 * Реализация в Infrastructure слое.
 *
 * @package CatVRF
 * @version 2026.1
 */


namespace App\Domains\Beauty\Domain\Repositories;

use App\Domains\Beauty\Domain\Entities\Appointment;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface AppointmentRepositoryInterface
{
    /**
     * @param AppointmentId $id
     * @return Appointment|null
     */
    public function findById(AppointmentId $id): ?Appointment;

    /**
     * @param MasterId $masterId
     * @param CarbonImmutable $from
     * @param CarbonImmutable $to
     * @return Collection<int, Appointment>
     */
    public function findByMasterForPeriod(MasterId $masterId, CarbonImmutable $from, CarbonImmutable $to): Collection;

    /**
     * @param Appointment $appointment
     * @return void
     */
    public function save(Appointment $appointment): void;

    /**
     * @param AppointmentId $id
     * @return bool
     */
    public function delete(AppointmentId $id): bool;

    /**
     * @return AppointmentId
     */
    public function nextIdentity(): AppointmentId;

    /**
     * Поиск записей по клиенту за период.
     *
     * @param int             $clientId ID клиента
     * @param CarbonImmutable $from     Начало периода
     * @param CarbonImmutable $to       Конец периода
     * @return Collection<int, Appointment>
     */
    public function findByClientForPeriod(int $clientId, CarbonImmutable $from, CarbonImmutable $to): Collection;
}

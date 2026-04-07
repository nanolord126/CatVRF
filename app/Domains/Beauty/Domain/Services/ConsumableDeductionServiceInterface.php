<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Beauty\Domain\Services;

use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;

/**
 * Контракт для сервиса автоматического списания расходников
 * при завершении услуги (паттерн: Domain Service Interface).
 *
 * Реализация — в Infrastructure/Services/ConsumableDeductionService.php.
 * Вызывается из CompleteAppointmentUseCase и DeductAppointmentConsumablesListener.
 */
interface ConsumableDeductionServiceInterface
{
    /**
     * Зарезервировать расходники на время записи (hold).
     * Вызывается при создании Appointment.
     *
     * @throws \DomainException если запасов недостаточно
     */
    public function holdConsumables(
        AppointmentId $appointmentId,
        ServiceId $serviceId,
        string $correlationId,
    ): void;

    /**
     * Снять резерв при отмене записи.
     * Вызывается при переходе в статус CANCELLED.
     */
    public function releaseHold(
        AppointmentId $appointmentId,
        ServiceId $serviceId,
        string $correlationId,
    ): void;

    /**
     * Списать расходники при завершении услуги.
     * Вызывается при переходе в статус COMPLETED.
     *
     * @throws \DomainException если запасов недостаточно (не должно возникать после hold)
     */
    public function deductForAppointment(
        AppointmentId $appointmentId,
        ServiceId $serviceId,
        string $correlationId,
    ): void;

    /**
     * Проверить, хватает ли расходников для выполнения услуги.
     */
    public function hasEnoughStock(ServiceId $serviceId): bool;
}

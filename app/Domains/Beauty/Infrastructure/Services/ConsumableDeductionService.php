<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Services;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Domain\Repositories\ConsumableRepositoryInterface;
use App\Domains\Beauty\Domain\Services\ConsumableDeductionServiceInterface;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Services\FraudControlService;

/**
 * Конкретная реализация списания расходников при работе с записями.
 * Все операции в $this->db->transaction() с лок-записями (lockForUpdate).
 */
final readonly class ConsumableDeductionService implements ConsumableDeductionServiceInterface
{
    public function __construct(
        private ConsumableRepositoryInterface $consumableRepository,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Зарезервировать расходники при создании записи (hold).
     */
    public function holdConsumables(
        AppointmentId $appointmentId,
        ServiceId $serviceId,
        string $correlationId,
    ): void {
        $this->db->transaction(function () use ($appointmentId, $serviceId, $correlationId): void {
            $consumables = $this->consumableRepository->findByServiceId($serviceId);

            foreach ($consumables as $consumable) {
                $consumable->hold($consumable->quantityPerService);
                $this->consumableRepository->save($consumable);

                $this->logger->info('Consumable hold applied', [
                    'correlation_id'  => $correlationId,
                    'appointment_id'  => $appointmentId->getValue(),
                    'consumable_id'   => $consumable->id,
                    'consumable_name' => $consumable->name,
                    'quantity'        => $consumable->quantityPerService,
                ]);
            }
        });
    }

    /**
     * Фактически списать расходники при завершении услуги.
     */
    public function deductForAppointment(
        AppointmentId $appointmentId,
        ServiceId $serviceId,
        string $correlationId,
    ): void {
        $this->db->transaction(function () use ($appointmentId, $serviceId, $correlationId): void {
            $consumables = $this->consumableRepository->findByServiceId($serviceId);

            foreach ($consumables as $consumable) {
                $consumable->deduct($consumable->quantityPerService);
                $this->consumableRepository->save($consumable);

                $this->logger->info('Consumable deducted', [
                    'correlation_id'  => $correlationId,
                    'appointment_id'  => $appointmentId->getValue(),
                    'consumable_id'   => $consumable->id,
                    'consumable_name' => $consumable->name,
                    'quantity'        => $consumable->quantityPerService,
                    'remaining_stock' => $consumable->currentStock,
                ]);

                if ($consumable->isBelowThreshold()) {
                    $this->logger->warning('Consumable below threshold', [
                        'correlation_id'  => $correlationId,
                        'consumable_id'   => $consumable->id,
                        'consumable_name' => $consumable->name,
                        'current_stock'   => $consumable->currentStock,
                        'threshold'       => $consumable->minStockThreshold,
                    ]);
                }
            }
        });
    }

    /**
     * Проверить наличие расходников для услуги (без мутаций).
     */
    public function hasEnoughStock(ServiceId $serviceId): bool
    {
        $consumables = $this->consumableRepository->findByServiceId($serviceId);

        foreach ($consumables as $consumable) {
            if ($consumable->availableStock() < (int) ceil($consumable->quantityPerService)) {
                return false;
            }
        }

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\DTOs\UpdatePaymentRecordDto;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentRecordCreated;
use App\Domains\Payment\Events\PaymentRecordUpdated;
use App\Domains\Payment\Models\PaymentRecord;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис управления платёжными записями.
 *
 * Отвечает за CRUD в таблице payment_transactions.
 * Не вызывает внешний шлюз напрямую (это делает PaymentCoordinatorService).
 */
final readonly class PaymentService
{
    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private FraudControlService $fraud,
        private AuditService $audit,
    ) {}

    /**
     * Создать платёжную запись.
     */
    public function create(CreatePaymentRecordDto $dto): PaymentRecord
    {
        $this->fraud->check($dto);

        return $this->db->transaction(function () use ($dto): PaymentRecord {
            $record = PaymentRecord::create(array_merge($dto->toArray(), [
                'status' => PaymentStatus::PENDING->value,
            ]));

            $this->logger->info('Payment record created', [
                'payment_record_id' => $record->id,
                'amount_kopecks' => $dto->amountKopecks,
                'provider_code' => $dto->providerCode,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            $this->audit->record(
                action: 'payment_record_created',
                subjectType: PaymentRecord::class,
                subjectId: $record->id,
                newValues: $dto->toAuditContext(),
                correlationId: $dto->correlationId,
            );

            event(new PaymentRecordCreated(
                paymentRecord: $record,
                correlationId: $dto->correlationId,
                userId: $this->getCurrentUserId(),
            ));

            return $record;
        });
    }

    /**
     * Обновить статус платежа (capture / refund / webhook).
     *
     * @throws \InvalidArgumentException если переход статуса запрещён
     */
    public function updateStatus(UpdatePaymentRecordDto $dto): PaymentRecord
    {
        return $this->db->transaction(function () use ($dto): PaymentRecord {
            /** @var PaymentRecord $record */
            $record = PaymentRecord::query()
                ->lockForUpdate()
                ->findOrFail($dto->paymentRecordId);

            $targetStatus = PaymentStatus::from($dto->status);

            if (! $record->canTransitionTo($targetStatus)) {
                throw new \InvalidArgumentException(
                    "Cannot transition from [{$record->status->value}] to [{$targetStatus->value}]"
                );
            }

            $oldValues = [
                'status' => $record->status->value,
                'provider_payment_id' => $record->provider_payment_id,
            ];

            $record->update($dto->toArray());
            $record->refresh();

            $newValues = [
                'status' => $record->status->value,
                'provider_payment_id' => $record->provider_payment_id,
            ];

            $this->logger->info('Payment record status updated', [
                'payment_record_id' => $record->id,
                'old_status' => $oldValues['status'],
                'new_status' => $newValues['status'],
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->record(
                action: 'payment_record_status_updated',
                subjectType: PaymentRecord::class,
                subjectId: $record->id,
                oldValues: $oldValues,
                newValues: $newValues,
                correlationId: $dto->correlationId,
            );

            event(new PaymentRecordUpdated(
                paymentRecord: $record,
                correlationId: $dto->correlationId,
                oldValues: $oldValues,
                newValues: $newValues,
                userId: $this->getCurrentUserId(),
            ));

            return $record;
        });
    }

    /**
     * Найти запись по ID (кэш не используем — платежи критичны к актуальности).
     */
    public function findById(int $id): ?PaymentRecord
    {
        return PaymentRecord::find($id);
    }

    /**
     * Найти запись по idempotency_key (для предотвращения дублей).
     */
    public function findByIdempotencyKey(string $key): ?PaymentRecord
    {
        return PaymentRecord::where('idempotency_key', $key)->first();
    }

    /**
     * ID текущего пользователя.
     */
    private function getCurrentUserId(): ?int
    {
        $user = $this->guard->user();

        return $user?->getAuthIdentifier();
    }
}

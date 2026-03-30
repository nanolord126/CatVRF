<?php declare(strict_types=1);

namespace App\Domains\Consulting\ProfessionalServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EscrowService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly PaymentService $payment,
            private readonly WalletService $wallet,
        ) {}

        /**
         * Создание контракта с депонированием средств (Hold).
         */
        public function openContract(int $clientId, int $providerId, int $totalAmount, array $milestones, string $correlationId = ""): Contract
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting
            if (RateLimiter::tooManyAttempts("escrow:open:{$clientId}", 3)) {
                throw new \RuntimeException("Слишком много попыток открытия контракта.", 429);
            }
            RateLimiter::hit("escrow:open:{$clientId}", 3600);

            return DB::transaction(function () use ($clientId, $providerId, $totalAmount, $milestones, $correlationId) {

                // 2. Fraud Check - проверка на отмывание денег через проф-услуги
                $fraud = $this->fraud->check([
                    "user_id" => $clientId,
                    "operation_type" => "escrow_contract_create",
                    "correlation_id" => $correlationId,
                    "meta" => ["provider_id" => $providerId, "amount" => $totalAmount]
                ]);

                if ($fraud["decision"] === "block") {
                    Log::channel("audit")->error("Escrow Security Block", ["client" => $clientId, "score" => $fraud["score"]]);
                    throw new \RuntimeException("Контракт заблокирован комплаенсом.", 403);
                }

                // 3. Создание контракта
                $contract = Contract::create([
                    "uuid" => (string) Str::uuid(),
                    "client_id" => $clientId,
                    "provider_id" => $providerId,
                    "total_amount_kopecks" => $totalAmount,
                    "status" => "active",
                    "correlation_id" => $correlationId
                ]);

                // 4. Создание этапов (Milestones)
                foreach ($milestones as $m) {
                    Milestone::create([
                        "contract_id" => $contract->id,
                        "title" => $m["title"],
                        "amount_kopecks" => $m["amount"],
                        "status" => "pending",
                    ]);
                }

                // 5. Hold средств на кошельке клиента
                $this->wallet->hold(
                    userId: $clientId,
                    amount: $totalAmount,
                    reason: "Escrow Contract #{$contract->id} initialized",
                    correlationId: $correlationId
                );

                Log::channel("audit")->info("Escrow: contract opened", ["contract_id" => $contract->id, "amount" => $totalAmount]);

                return $contract;
            });
        }

        /**
         * Приемка этапа и выплата исполнителю.
         */
        public function releaseMilestone(int $milestoneId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $milestone = Milestone::with("contract")->findOrFail($milestoneId);

            if ($milestone->status !== "pending") {
                throw new \RuntimeException("Этап уже оплачен или отменен.");
            }

            DB::transaction(function () use ($milestone, $correlationId) {
                $contract = $milestone->contract;

                // Обновляем статус этапа
                $milestone->update(["status" => "completed", "paid_at" => now()]);

                // Расчет 14% комиссии платформы
                $total = $milestone->amount_kopecks;
                $platformFee = (int) ($total * 0.14);
                $providerPayout = $total - $platformFee;

                // 6. Release Hold (списание с клиента)
                $this->wallet->releaseHold(
                    userId: $contract->client_id,
                    amount: $total,
                    correlationId: $correlationId
                );

                // 7. Дебет клиента (реальный перевод)
                $this->wallet->debit(
                    userId: $contract->client_id,
                    amount: $total,
                    type: "escrow_payment",
                    reason: "Payment for Milestone #{$milestone->id}",
                    correlationId: $correlationId
                );

                // 8. Кредит исполнителю (за вычетом 14%)
                $this->wallet->credit(
                    userId: $contract->provider_id,
                    amount: $providerPayout,
                    type: "escrow_payout",
                    reason: "Milestone #{$milestone->id} completed",
                    correlationId: $correlationId
                );

                Log::channel("audit")->info("Escrow: milestone released", ["milestone_id" => $milestone->id, "payout" => $providerPayout]);
            });
        }
}

<?php declare(strict_types=1);

namespace Modules\Finances\Services\Recurring;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RecurringPaymentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Subscription, WalletCard};
    use App\Domains\Finances\Services\PaymentService;
    use App\Models\User;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\{Log, DB, Auth};
    use Exception;
    
    /**
     * Сервис управления повторяющимися платежами по подпискам.
     */
    class RecurringPaymentService
    {
        public function __construct(private PaymentService $paymentService) {}
    
        /**
         * Обработать все подписки для очередного цикла платежей.
         */
        public function processRecurringPayments(): array
        {
            try {
                // Найти все активные подписки, у которых пришёл срок платежа
                $subscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
                    ->where('next_payment_at', '<=', Carbon::now())
                    ->get();
    
                $results = [
                    'processed' => 0,
                    'failed' => 0,
                    'details' => [],
                ];
    
                foreach ($subscriptions as $subscription) {
                    try {
                        $this->chargeSubscription($subscription);
                        $results['processed']++;
                        $results['details'][] = ['subscription_id' => $subscription->id, 'status' => 'charged'];
                    } catch (Exception $e) {
                        $results['failed']++;
                        $results['details'][] = [
                            'subscription_id' => $subscription->id,
                            'status' => 'failed',
                            'error' => $e->getMessage(),
                        ];
                        Log::error('Subscription charge failed', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
    
                Log::info('Recurring payments processed', [
                    'processed' => $results['processed'],
                    'failed' => $results['failed'],
                ]);
    
                return $results;
            } catch (Exception $e) {
                Log::error('Process recurring payments failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Выполнить платёж по подписке используя токен карты.
         */
        private function chargeSubscription(Subscription $subscription): void
        {
            DB::beginTransaction();
            try {
                // Получить сохранённую карту
                $card = $subscription->walletCard;
                if (!$card || !$card->is_active) {
                    throw new Exception('Card not available or inactive');
                }
    
                // Проверить что карта не истекла
                if ($card->isExpired()) {
                    $subscription->update(['status' => Subscription::STATUS_FAILED]);
                    throw new Exception('Card expired');
                }
    
                // Выполнить платёж по токену через шлюз
                $result = $this->paymentService->chargeByToken(
                    $card->token,
                    $subscription->amount,
                    [
                        'subscription_id' => $subscription->id,
                        'order_id' => "SUB-{$subscription->id}-" . Carbon::now()->timestamp,
                        'description' => $subscription->metadata['plan_name'] ?? 'Subscription Payment',
                    ]
                );
    
                // Обновить дату следующего платежа
                $subscription->update([
                    'next_payment_at' => $subscription->getNextPaymentDate(),
                    'last_payment_at' => Carbon::now(),
                ]);
    
                Log::info('Subscription charged successfully', [
                    'subscription_id' => $subscription->id,
                    'amount' => $subscription->amount,
                    'next_payment' => $subscription->next_payment_at,
                ]);
    
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Failed to charge subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    
        /**
         * Создать новую подписку.
         */
        public function createSubscription(array $data): Subscription
        {
            try {
                // Валидация
                if ($data['amount'] <= 0) {
                    throw new Exception('Invalid subscription amount');
                }
    
                /** @var User|null $user */
                $user = Auth::user();
                if (!$user) {
                    throw new Exception('User not authenticated');
                }
    
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'wallet_card_id' => $data['wallet_card_id'],
                    'amount' => $data['amount'],
                    'frequency' => $data['frequency'],
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => $data['starts_at'] ?? Carbon::now(),
                    'next_payment_at' => $this->calculateNextPaymentDate($data['frequency']),
                    'tenant_id' => auth('tenant')->user()->id,
                    'metadata' => $data['metadata'] ?? [],
                ]);
    
                Log::info('Subscription created', [
                    'subscription_id' => $subscription->id,
                    'frequency' => $data['frequency'],
                    'amount' => $data['amount'],
                ]);
    
                return $subscription;
            } catch (Exception $e) {
                Log::error('Failed to create subscription', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Отменить подписку.
         */
        public function cancelSubscription(Subscription $subscription, ?string $reason = null): void
        {
            try {
                $subscription->cancel($reason);
                Log::info('Subscription cancelled', [
                    'subscription_id' => $subscription->id,
                    'reason' => $reason,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to cancel subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    
        /**
         * Рассчитать следующую дату платежа.
         */
        private function calculateNextPaymentDate(string $frequency): Carbon
        {
            return match ($frequency) {
                Subscription::FREQUENCY_DAILY => Carbon::now()->addDay(),
                Subscription::FREQUENCY_WEEKLY => Carbon::now()->addWeek(),
                Subscription::FREQUENCY_MONTHLY => Carbon::now()->addMonth(),
                Subscription::FREQUENCY_YEARLY => Carbon::now()->addYear(),
                default => Carbon::now()->addMonth(),
            };
        }
    
        /**
         * Переключить карту для подписки.
         */
        public function updateCard(Subscription $subscription, int $walletCardId): Subscription
        {
            $subscription->update(['wallet_card_id' => $walletCardId]);
            Log::info('Subscription card updated', [
                'subscription_id' => $subscription->id,
                'wallet_card_id' => $walletCardId,
            ]);
            return $subscription;
        }
    
        /**
         * Получить статистику по подпискам пользователя.
         */
        public function getUserSubscriptionStats(int $userId): array
        {
            $subscriptions = Subscription::where('user_id', $userId)->get();
    
            return [
                'total' => $subscriptions->count(),
                'active' => $subscriptions->where('status', Subscription::STATUS_ACTIVE)->count(),
                'paused' => $subscriptions->where('status', Subscription::STATUS_PAUSED)->count(),
                'total_monthly_amount' => $subscriptions
                    ->where('frequency', Subscription::FREQUENCY_MONTHLY)
                    ->sum('amount'),
            ];
        }
}

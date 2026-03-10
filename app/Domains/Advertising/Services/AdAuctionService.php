<?php

namespace App\Domains\Advertising\Services;

use App\Domains\Advertising\Models\AdAuctionBid;
use App\Domains\Advertising\Models\AdBanner;
use App\Domains\Finances\Services\WalletService;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * AdAuctionService - Сервис управления рекламными аукционами (Production 2026).
 * 
 * Отвечает за:
 * - Размещение ставок (bids) на показы баннеров в слотах
 * - Холдирование средств с кошельков рекламодателей
 * - Расчеты при закрытии аукциона
 * - Логирование и аудит транзакций
 */
class AdAuctionService
{
    private string $correlationId;

    public function __construct(private WalletService $wallet)
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Размещение ставки в аукционе на конкретный рекламный слот.
     * 
     * Проверяет корректность параметров, выполняет холдирование средств
     * и создает запись ставки с полным аудит-логированием.
     *
     * @param int $bannerId ID баннера
     * @param int $placementId ID плейсмента (слота)
     * @param float $cpm Цена за 1000 показов (в рублях)
     * @param int $impressions Минимальное количество показов
     * 
     * @return AdAuctionBid Созданная или обновленная ставка
     * 
     * @throws \InvalidArgumentException При некорректных параметрах
     * @throws \Exception При ошибке холдирования средств
     * @throws \RuntimeException При недоступности кошелька
     */
    public function placeBid(
        int $bannerId,
        int $placementId,
        float $cpm,
        int $impressions = 10000
    ): AdAuctionBid {
        try {
            // === Валидация параметров ===
            if ($impressions < 10000) {
                throw new \InvalidArgumentException(
                    "Минимальный пакет показов для аукционной модели - 10,000. Получено: {$impressions}"
                );
            }

            if ($cpm <= 0) {
                throw new \InvalidArgumentException(
                    "CPM должен быть больше 0. Получено: {$cpm}"
                );
            }

            // === Получение баннера ===
            $banner = AdBanner::findOrFail($bannerId);

            if (!$banner->campaign) {
                throw new \RuntimeException(
                    "Баннер {$bannerId} не имеет связанной кампании"
                );
            }

            // === Расчет бюджета ===
            $totalBudget = ($cpm / 1000) * $impressions;

            Log::info('Placing ad auction bid', [
                'banner_id' => $bannerId,
                'placement_id' => $placementId,
                'cpm' => $cpm,
                'impressions' => $impressions,
                'total_budget' => $totalBudget,
                'correlation_id' => $this->correlationId,
            ]);

            // === Транзакция: холдирование + создание ставки ===
            return DB::transaction(function() use (
                $banner,
                $bannerId,
                $placementId,
                $cpm,
                $impressions,
                $totalBudget
            ) {
                try {
                    // 1. Проверка достаточности бюджета кампании
                    if ($banner->campaign->getRemainingBudgetAttribute() < $totalBudget) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                "Недостаточно средств в кампании. Требуется: %.2f, доступно: %.2f",
                                $totalBudget,
                                $banner->campaign->getRemainingBudgetAttribute()
                            )
                        );
                    }

                    // 2. Холдирование средств с кошелька рекламодателя (Tenant Wallet)
                    $walletTransaction = $this->wallet->debit(
                        $banner->campaign->user,
                        $totalBudget,
                        "Ad Auction Hold: {$impressions} impressions @ {$cpm} CPM"
                    );

                    Log::debug('Wallet debit processed', [
                        'user_id' => $banner->campaign->user->id,
                        'amount' => $totalBudget,
                        'transaction_id' => $walletTransaction?->id,
                        'correlation_id' => $this->correlationId,
                    ]);

                    // 3. Создание или обновление ставки
                    $bid = AdAuctionBid::updateOrCreate(
                        ['ad_banner_id' => $bannerId, 'placement_id' => $placementId],
                        [
                            'cpm_bid' => $cpm,
                            'min_impressions' => $impressions,
                            'total_budget' => $totalBudget,
                            'is_active' => true,
                            'correlation_id' => $this->correlationId,
                            'wallet_hold_transaction_id' => $walletTransaction?->id,
                        ]
                    );

                    // 4. Логирование в audit trail
                    AuditLog::create([
                        'action' => 'advertising.auction_bid_placed',
                        'description' => "Ставка размещена: {$cpm} CPM, {$impressions} показов",
                        'model_type' => 'AdAuctionBid',
                        'model_id' => $bid->id,
                        'correlation_id' => $this->correlationId,
                        'metadata' => [
                            'banner_id' => $bannerId,
                            'placement_id' => $placementId,
                            'cpm' => $cpm,
                            'impressions' => $impressions,
                            'total_budget' => $totalBudget,
                            'wallet_transaction_id' => $walletTransaction?->id,
                        ],
                    ]);

                    Log::info('Ad auction bid placed successfully', [
                        'bid_id' => $bid->id,
                        'banner_id' => $bannerId,
                        'total_budget' => $totalBudget,
                        'correlation_id' => $this->correlationId,
                    ]);

                    return $bid;

                } catch (Throwable $e) {
                    Log::error('Error in auction bid placement transaction', [
                        'banner_id' => $bannerId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $this->correlationId,
                    ]);

                    throw $e;
                }
            });

        } catch (\Throwable $e) {
            // Логирование ошибки
            AuditLog::create([
                'action' => 'advertising.auction_bid_failed',
                'description' => "Ошибка при размещении ставки: {$e->getMessage()}",
                'model_type' => 'AdAuctionBid',
                'model_id' => $bannerId,
                'correlation_id' => $this->correlationId,
                'metadata' => [
                    'error' => $e->getMessage(),
                    'banner_id' => $bannerId,
                    'placement_id' => $placementId,
                ],
            ]);

            Log::error('Ad auction bid placement failed', [
                'banner_id' => $bannerId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);

            throw new \Exception("Не удалось разместить ставку: {$e->getMessage()}");
        }
    }

    /**
     * Закрытие аукциона и расчет с рекламодателем.
     * 
     * После окончания периода показов:
     * - Возвращает неиспользованный бюджет (если показов меньше minimum)
     * - Списывает потраченную сумму за реально показанные баннеры
     * - Логирует все операции
     *
     * @param AdAuctionBid $bid Закрываемая ставка
     * @param int $deliveredImpressions Реально доставленные показы
     * 
     * @return array Результат расчета ['returned' => float, 'charged' => float]
     * 
     * @throws \Exception При ошибке расчета
     */
    public function settleAuction(AdAuctionBid $bid, int $deliveredImpressions): array
    {
        try {
            Log::info('Settling ad auction', [
                'bid_id' => $bid->id,
                'delivered_impressions' => $deliveredImpressions,
                'minimum_impressions' => $bid->min_impressions,
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function() use ($bid, $deliveredImpressions) {
                $actualCharge = ($bid->cpm_bid / 1000) * max($deliveredImpressions, 0);
                $returned = max(0, $bid->total_budget - $actualCharge);

                // Возврат неиспользованного бюджета (если не достигнут минимум)
                if ($returned > 0) {
                    $banner = $bid->banner;
                    if ($banner && $banner->campaign) {
                        $this->wallet->credit(
                            $banner->campaign->user,
                            $returned,
                            "Ad Auction Refund: {$deliveredImpressions} impressions delivered"
                        );

                        Log::info('Auction refund credited', [
                            'bid_id' => $bid->id,
                            'amount' => $returned,
                            'correlation_id' => $this->correlationId,
                        ]);
                    }
                }

                // Обновление ставки
                $bid->update([
                    'is_active' => false,
                    'delivered_impressions' => $deliveredImpressions,
                    'final_charge' => $actualCharge,
                    'settled_at' => now(),
                ]);

                AuditLog::create([
                    'action' => 'advertising.auction_settled',
                    'description' => "Аукцион закрыт: {$deliveredImpressions} показов",
                    'model_type' => 'AdAuctionBid',
                    'model_id' => $bid->id,
                    'correlation_id' => $this->correlationId,
                    'metadata' => [
                        'delivered_impressions' => $deliveredImpressions,
                        'charged' => $actualCharge,
                        'returned' => $returned,
                    ],
                ]);

                return ['returned' => $returned, 'charged' => $actualCharge];
            });

        } catch (Throwable $e) {
            Log::error('Auction settlement failed', [
                'bid_id' => $bid->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);

            throw new \Exception("Не удалось закрыть аукцион: {$e->getMessage()}");
        }
    }
}

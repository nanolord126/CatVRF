<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services\AI;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * AI-конструктор для домена Wallet.
 *
 * Анализирует транзакции и баланс пользователя,
 * выдаёт рекомендации по оптимизации расходов,
 * бонусов и кэшбэка.
 *
 * CANON 2026: final readonly, constructor DI, FraudControlService::check(),
 * DB::transaction(), correlation_id, AuditService::record().
 */
final readonly class WalletConstructorService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const CACHE_PREFIX = 'wallet_ai:';

    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private FraudControlService $fraud,
        private AuditService $audit,
        private CacheRepository $cache,
    ) {}

    /**
     * Анализировать кошелёк и дать AI-рекомендации.
     *
     * @return array{
     *     success: bool,
     *     wallet_id: int,
     *     analysis: array{
     *         total_deposits: int,
     *         total_withdrawals: int,
     *         net_flow: int,
     *         avg_transaction: int,
     *         top_categories: list<string>,
     *     },
     *     recommendations: list<array{type: string, message: string, priority: string}>,
     *     correlation_id: string,
     * }
     */
    public function analyzeAndRecommend(int $walletId, int $userId, string $correlationId): array
    {
        $this->fraud->check([
            'action' => 'wallet_ai_analyze',
            'wallet_id' => $walletId,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        $cacheKey = self::CACHE_PREFIX . $walletId;
        $cached = $this->cache->get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $wallet = Wallet::findOrFail($walletId);

        $analysis = $this->buildAnalysis($wallet);
        $recommendations = $this->generateRecommendations($analysis, $wallet);

        $result = [
            'success' => true,
            'wallet_id' => $walletId,
            'analysis' => $analysis,
            'recommendations' => $recommendations,
            'correlation_id' => $correlationId,
        ];

        $this->cache->put($cacheKey, $result, self::CACHE_TTL_SECONDS);

        $this->logger->info('Wallet AI analysis completed', [
            'wallet_id' => $walletId,
            'user_id' => $userId,
            'recommendations_count' => count($recommendations),
            'correlation_id' => $correlationId,
        ]);

        $this->audit->record(
            action: 'wallet_ai_analyzed',
            subjectType: Wallet::class,
            subjectId: $walletId,
            correlationId: $correlationId,
            newValues: ['recommendations_count' => count($recommendations)],
        );

        return $result;
    }

    /** Собираем аналитику по транзакциям кошелька. */
    private function buildAnalysis(Wallet $wallet): array
    {
        $transactions = $this->db->table('balance_transactions')
            ->where('wallet_id', $wallet->id)
            ->get();

        $totalDeposits = 0;
        $totalWithdrawals = 0;
        $count = 0;

        foreach ($transactions as $tx) {
            $type = BalanceTransactionType::tryFrom($tx->type);

            if ($type !== null && $type->isCredit()) {
                $totalDeposits += (int) $tx->amount;
            } elseif ($type !== null && $type->isDebit()) {
                $totalWithdrawals += (int) $tx->amount;
            }

            $count++;
        }

        return [
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'net_flow' => $totalDeposits - $totalWithdrawals,
            'avg_transaction' => $count > 0 ? (int) round(($totalDeposits + $totalWithdrawals) / $count) : 0,
            'top_categories' => $this->extractTopCategories($transactions),
        ];
    }

    /** Генерируем рекомендации на основе аналитики. */
    private function generateRecommendations(array $analysis, Wallet $wallet): array
    {
        $recommendations = [];

        if ($analysis['net_flow'] < 0) {
            $recommendations[] = [
                'type' => 'spending_alert',
                'message' => 'Расходы превышают доходы. Рекомендуем пересмотреть бюджет.',
                'priority' => 'high',
            ];
        }

        if ($wallet->current_balance > 0 && $wallet->hold_amount > $wallet->current_balance * 0.5) {
            $recommendations[] = [
                'type' => 'hold_alert',
                'message' => 'Более 50% баланса заморожено. Проверьте незавершённые операции.',
                'priority' => 'medium',
            ];
        }

        if ($analysis['total_deposits'] === 0) {
            $recommendations[] = [
                'type' => 'activation',
                'message' => 'Кошелёк пуст. Пополните баланс для доступа ко всем функциям.',
                'priority' => 'low',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'healthy',
                'message' => 'Финансовые показатели в норме.',
                'priority' => 'low',
            ];
        }

        return $recommendations;
    }

    /**
     * Извлечение TOP категорий из транзакций.
     *
     * @return list<string>
     */
    private function extractTopCategories(mixed $transactions): array
    {
        $counts = [];

        foreach ($transactions as $tx) {
            $type = $tx->type ?? 'unknown';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, 3);
    }
}

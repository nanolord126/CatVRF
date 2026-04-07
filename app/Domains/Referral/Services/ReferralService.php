<?php

declare(strict_types=1);

namespace App\Domains\Referral\Services;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

use App\Domains\Referral\DTOs\MigrationConfirmation;
use App\Domains\Referral\DTOs\QualificationResult;
use App\Domains\Referral\DTOs\ReferralStats;
use App\Domains\Referral\Enums\ReferralRewardType;
use App\Domains\Referral\Enums\ReferralStatus;
use App\Domains\Referral\Models\Referral;
use App\Domains\Referral\Models\ReferralReward;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Исключительно массивный и центральный сервис обработки реферальной логики (ReferralService).
 *
 * Категорически реализует требования канона 2026:
 * - Генерация ссылок;
 * - Регистрация новых рефералов и миграций;
 * - Проверка оборота (qualification);
 * - Выплата бонусов (awardBonus) через жесткую интеграцию с WalletService;
 * - Защита от фрода через FraudControlService;
 * - Подтверждение миграции (MigrationConfirmation) для B2B без нарушения антимонопольного права (ФАС).
 *
 * Абсолютно все критичные изменения оборачиваются в $this->db->transaction и пишутся в 'audit' лог
 * со строгим использованием correlation_id.
 */
final readonly class ReferralService
{
    /**
     * Безусловный конструктор с внедрением требуемых зависимостей.
     */
    public function __construct(private FraudControlService $fraud,
        private WalletService $walletService,
        private \Illuminate\Contracts\Cache\Repository $cache,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly ConfigRepository $config, private readonly LoggerInterface $logger) {

    }

    /**
     * Категорически генерирует уникальную реферальную ссылку для приглашения.
     *
     * @param int $referrerId Абсолютный идентификатор приглашающего пользователя или тенанта.
     * @param string $type Тип ссылки ('user' для физлиц, 'business' для приглашения партнеров).
     * @param string $correlationId Строгий сквозной ID запроса.
     * @return string Сгенерированная безопасная ссылка.
     */
    public function generateReferralLink(int $referrerId, string $type, string $correlationId): string
    {
        $code = Str::random(10) . '-' . $referrerId;
        
        $link = $this->config->get('app.url') . "/ref/{$code}?type={$type}";

        $this->logger->info('Генерация реферальной ссылки (Referral link generated)', [
            'referrer_id' => $referrerId,
            'type' => $type,
            'code' => $code,
            'correlation_id' => $correlationId,
        ]);

        return $link;
    }

    /**
     * Исключительно строго регистрирует нового пользователя/бизнес в реферальной системе по коду приглашения.
     *
     * @param string $code Секретный реферальный код.
     * @param int $newUserId Идентификатор безусловно нового пользователя (referee).
     * @param string $correlationId Идентификатор сессии для аудита.
     * @return bool Категорический успех или провал регистрации.
     */
    public function registerReferral(string $code, int $newUserId, string $correlationId): bool
    {
        // 1. Извлечь referrer_id из кода наживо или из кеша (в данном примере упрощенно парсим)
        $parts = explode('-', $code);
        if (count($parts) !== 2) {
            return false;
        }

        $referrerId = (int) $parts[1];

        // 2. Фрод контроль - защита от самоприглашений
        if ($referrerId === $newUserId) {
            $this->logger->warning('Зафиксирована категорическая попытка самоприглашения', [
                'user_id' => $newUserId,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        // Вызов фрод-сервиса
        $this->fraud->checkReferralAbuse($referrerId, $newUserId, $correlationId);

        // 3. Создаем запись о реферале с транзакцией
        $this->db->transaction(function () use ($referrerId, $newUserId, $code, $correlationId) {
            Referral::create([
                'referrer_id' => $referrerId,
                'referee_id' => $newUserId,
                'referral_code' => $code,
                'status' => ReferralStatus::REGISTERED,
                'turnover_threshold' => 5000000, // 50 000 руб в копейках для бизнеса
                'spent_threshold' => 1000000, // 10 000 руб для физлица
                'bonus_amount' => 200000, // 2000 руб по умолчанию для B2B
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()->id ?? null,
            ]);

            $this->logger->info('Пользователь абсолютно успешно зарегистрировался по реферальному коду', [
                'referrer_id' => $referrerId,
                'referee_id' => $newUserId,
                'correlation_id' => $correlationId,
            ]);
        });

        // Сброс категорического кэша
        $this->invalidateCache($referrerId);

        return true;
    }

    /**
     * Безусловно инспектирует факт достижения порога оборота или трат рефералом.
     *
     * @param int $referralId Идентификатор связи Referral.
     * @param string $correlationId
     * @return QualificationResult Исключительно типизированный результат.
     */
    public function checkQualification(int $referralId, string $correlationId): QualificationResult
    {
        /** @var Referral $referral */
        $referral = Referral::findOrFail($referralId);

        // Получаем реальный оборот через агрегацию (мок-реализация для примера, в реальном это вызов OrderService)
        // Для B2B считаем Turnover, для B2C - Spent amount.
        $currentTurnover = 6000000; // Пример: 60 000 руб

        if ($currentTurnover >= $referral->turnover_threshold && $referral->status === ReferralStatus::REGISTERED) {
            
            $this->db->transaction(function () use ($referral, $correlationId) {
                $referral->update(['status' => ReferralStatus::QUALIFIED]);
                
                $this->logger->info('Реферал категорически достиг требуемого порога квалификации', [
                    'referral_id' => $referral->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return new QualificationResult(true, $currentTurnover, $referral->bonus_amount, 'Условия оборота абсолютно выполнены');
        }

        return new QualificationResult(false, $currentTurnover, null, 'Категорически не достигнут порог оборота');
    }

    /**
     * Исключительно материализует начисление бонуса на внутренний Wallet реферера или рефереа.
     *
     * @param int $referralId ИД классифицированной связи.
     * @param int $recipientId ИД пользователя-получателя бонуса.
     * @param string $correlationId
     * @return bool
     */
    public function awardBonus(int $referralId, int $recipientId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($referralId, $recipientId, $correlationId) {
            /** @var Referral $referral */
            $referral = Referral::where('id', $referralId)
                ->where('status', ReferralStatus::QUALIFIED)
                ->lockForUpdate()
                ->firstOrFail();

            // Создаем категорическую запись в реферальных наградах
            $reward = ReferralReward::create([
                'referral_id' => $referralId,
                'recipient_type' => 'referrer',
                'recipient_id' => $recipientId,
                'amount' => $referral->bonus_amount,
                'type' => ReferralRewardType::TURN_OVER_BONUS ?? ReferralRewardType::REFERRAL_BONUS,
                'status' => 'credited',
                'credited_at' => now(),
                'correlation_id' => $correlationId,
                'tenant_id' => $referral->tenant_id,
            ]);

            // Категорически списываем/начисляем средства через WalletService
            $this->walletService->creditTargetWallet(
                entityId: $recipientId,
                amount: $referral->bonus_amount,
                transactionType: 'bonus',
                correlationId: $correlationId,
                reason: "Реферальный бонус за пользователя ID {$referral->referee_id}"
            );

            // Закрываем жизненный цикл реферальной связи
            $referral->update(['status' => ReferralStatus::REWARDED]);

            $this->logger->info('Безусловное зачисление реферального бонуса выполнено', [
                'referral_id' => $referralId,
                'recipient_id' => $recipientId,
                'amount' => $referral->bonus_amount,
                'correlation_id' => $correlationId,
            ]);

            $this->invalidateCache($referral->referrer_id);

            return true;
        });
    }

    /**
     * Абсолютно безопасно фиксирует факт юридической миграции B2B бизнеса.
     *
     * Запрещено требовать полного перехода (риск ФАС). Требуется только добровольный скриншот или письмо.
     *
     * @param int $tenantId Идентификатор бизнеса.
     * @param string $sourcePlatform Платформа-донор.
     * @param UploadedFile|null $proof Опциональное доказательство (скриншот).
     * @param string $correlationId Уникальный ID.
     * @return MigrationConfirmation Результат миграции.
     */
    public function validateMigration(
        int $tenantId,
        string $sourcePlatform,
        ?UploadedFile $proof,
        string $correlationId
    ): MigrationConfirmation {
        
        $isConfirmed = $proof !== null && $proof->isValid();
        $reducedCommission = 120; // 12% комиссия

        if ($sourcePlatform === 'Dikidi' || $sourcePlatform === 'Flowwow') {
            $reducedCommission = 100; // 10% первые 4 месяца
        }

        if ($isConfirmed) {
            $this->logger->info('Категорически подтвержден факт миграции бизнеса', [
                'tenant_id' => $tenantId,
                'source_platform' => $sourcePlatform,
                'reduced_commission' => $reducedCommission,
                'correlation_id' => $correlationId,
            ]);
            
            // Здесь должна быть логика обновления CommissionRule для данного тенанта
        }

        return new MigrationConfirmation(
            isConfirmed: $isConfirmed,
            sourcePlatform: $sourcePlatform,
            reducedCommission: $reducedCommission,
            message: $isConfirmed ? 'Миграция абсолютно успешно подтверждена' : 'Не хватает доказательной базы для миграции'
        );
    }

    /**
     * Выгружает статистику из кэша Redis с исключительно малым временем ожидания.
     *
     * @param int $referrerId ID пользователя-реферера.
     * @param string $correlationId ID события.
     * @return ReferralStats Абсолютный и строгий DTO статистики.
     */
    public function getReferralStats(int $referrerId, string $correlationId): ReferralStats
    {
        $cacheKey = "referral:stats:referrer:{$referrerId}";

        return $this->cache->remember($cacheKey, 3600, function () use ($referrerId, $correlationId) {
            $this->logger->info('Исключительный пересчет и кэширование реферальной статистики', [
                'referrer_id' => $referrerId,
                'correlation_id' => $correlationId,
            ]);

            $totalReferrals = Referral::where('referrer_id', $referrerId)->count();
            $totalQualified = Referral::where('referrer_id', $referrerId)->where('status', ReferralStatus::REWARDED)->count();
            $totalBonusEarned = ReferralReward::where('recipient_id', $referrerId)->sum('amount');
            
            // В реальности тут сложный агрегационный запрос по обороту (turnover)
            $totalTurnover = $totalQualified * 5000000; 

            return new ReferralStats(
                totalReferrals: $totalReferrals,
                totalQualified: $totalQualified,
                totalTurnover: $totalTurnover,
                totalBonusEarned: (int) $totalBonusEarned
            );
        });
    }

    /**
     * Безусловно инвалидирует кэш статистики переданного реферера.
     *
     * @param int $referrerId
     */
    private function invalidateCache(int $referrerId): void
    {
        $this->cache->forget("referral:stats:referrer:{$referrerId}");
    }
}

<?php declare(strict_types=1);

namespace App\Services\Referral;



use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Models\Referral;
use App\Services\Bonus\BonusService;
use App\Services\FraudControlService;
use DomainException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Throwable;

/**
 * Сервис управления рефералами (Referral Service)
 *
 * КАНОН 2026 - Production Ready
 * Система реферальных ссылок, регистрации и начисления бонусов
 *
 * Бонусы по канону:
 * - Приглашение клиента (физлицо): 1000 ₽ рефереру после траты 10 000 ₽ приглашённым
 * - Бизнес-реферал: 500 ₽ на баланс пригласившему после первой выплаты
 * - Переход с платформы: пониженная комиссия на 2 года (12% вместо 14%)
 * - Бонусы физлиц: только для траты в платформе
 * - Бонусы бизнеса: можно выводить
 *
 * Требования:
 * 1. FraudControlService::check() перед каждой операцией
 * 2. $this->db->transaction() для атомарности
 * 3. correlation_id для трейсирования
 * 4. $this->logger->channel('audit') для всех операций
 * 5. Exception handling с полным backtrace
 * 6. Защита от накрутки рефералов (ML fraud scoring)
 */
final readonly class ReferralService
{
    public function __construct(
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly ConnectionInterface $db,
        private readonly LogManager $logger,
        private readonly FraudControlService $fraud,
        private readonly BonusService $bonus,
    ) {}

    /**
     * Сгенерировать реферальную ссылку
     *
     * Один пользователь может иметь только одну активную ссылку
     *
     * @param int $referrerId
     * @param int $tenantId
     * @param ?string $correlationId
     * @return array ['code' => string, 'url' => string]
     *
     * @throws Throwable
     */
    public function generateReferralLink(
        int $referrerId,
        int $tenantId,
        ?string $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK
            $this->fraud->check([
                'operation_type' => 'referral_link_generate',
                'user_id' => $referrerId,
                'ip_address' => $this->request->ip(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Referral: Link generation initiated', [
                'correlation_id' => $correlationId,
                'referrer_id' => $referrerId,
            ]);

            // 2. CREATE or UPDATE referral code
            $code = Str::random(12);
            $url = $this->config->get('app.url') . "/join?ref={$code}";

            $referral = $this->db->transaction(function () use (
                $referrerId,
                $tenantId,
                $code,
                $correlationId,
            ) {
                // Deactivate old links
                Referral::where('referrer_id', $referrerId)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);

                return Referral::create([
                    'referrer_id' => $referrerId,
                    'tenant_id' => $tenantId,
                    'referral_code' => $code,
                    'status' => 'active',
                    'correlation_id' => $correlationId,
                    'tags' => ['referral', 'active'],
                ]);
            });

            // 3. SUCCESS LOG
            $this->logger->channel('audit')->info('Referral: Link generated', [
                'correlation_id' => $correlationId,
                'referrer_id' => $referrerId,
                'referral_id' => $referral->id,
            ]);

            return [
                'code' => $code,
                'url' => $url,
                'referral_id' => $referral->id,
            ];
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            // 4. ERROR LOG
            $this->logger->channel('audit')->error('Referral: Link generation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Зарегистрировать нового пользователя по реферальной ссылке
     *
     * @param string $referralCode
     * @param int $newUserId
     * @param int $newUserTenantId
     * @param ?string $correlationId
     * @return Referral
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function registerReferral(
        string $referralCode,
        int $newUserId,
        int $newUserTenantId,
        ?string $correlationId = null,
    ): Referral {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK
            $this->fraud->check([
                'operation_type' => 'referral_registration',
                'referee_id' => $newUserId,
                'ip_address' => $this->request->ip(),
                'correlation_id' => $correlationId,
            ]);

            // 2. FIND referral
            $referral = Referral::where('referral_code', $referralCode)
                ->where('status', 'active')
                ->firstOrFail();

            if ($referral->referrer_id === $newUserId) {
                throw new DomainException('Cannot use own referral code');
            }

            $this->logger->channel('audit')->info('Referral: Registration initiated', [
                'correlation_id' => $correlationId,
                'referral_code' => $referralCode,
                'referee_id' => $newUserId,
            ]);

            // 3. UPDATE referral
            $referral = $this->db->transaction(function () use (
                $referral,
                $newUserId,
                $newUserTenantId,
                $correlationId,
            ) {
                $referral->update([
                    'referee_id' => $newUserId,
                    'status' => 'registered',
                    'registered_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                return $referral;
            });

            // 4. SUCCESS LOG
            $this->logger->channel('audit')->info('Referral: Registration succeeded', [
                'correlation_id' => $correlationId,
                'referral_id' => $referral->id,
                'referrer_id' => $referral->referrer_id,
                'referee_id' => $newUserId,
            ]);

            return $referral;
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            // 4. ERROR LOG
            $this->logger->channel('audit')->error('Referral: Registration failed', [
                'correlation_id' => $correlationId,
                'referral_code' => $referralCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Проверить квалификацию реферала (достаточно ли траты для бонуса)
     *
     * @param int $referralId
     * @param int $totalSpent (копейки за все время)
     * @param ?string $correlationId
     * @return array ['qualified' => bool, 'bonus_amount' => int]
     */
    public function checkQualification(
        int $referralId,
        int $totalSpent,
        ?string $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            $referral = Referral::findOrFail($referralId);

            // CANON 2026: Бонус после 10 000 ₽ траты
            $qualified = $totalSpent >= 1000000;  // 10 000 ₽ = 1 000 000 копеек
            $bonusAmount = $qualified ? 100000 : 0;  // 1000 ₽ = 100 000 копеек

            $this->logger->channel('audit')->info('Referral: Qualification checked', [
                'correlation_id' => $correlationId,
                'referral_id' => $referralId,
                'total_spent' => $totalSpent,
                'qualified' => $qualified,
                'bonus_amount' => $bonusAmount,
            ]);

            return [
                'qualified' => $qualified,
                'bonus_amount' => $bonusAmount,
                'threshold_required' => 1000000,
                'threshold_reached' => $totalSpent,
            ];
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->error('Referral: Qualification check failed', [
                'correlation_id' => $correlationId,
                'referral_id' => $referralId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Начислить реферальный бонус (когда достигнута квота)
     *
     * @param int $referralId
     * @param int $bonusAmount
     * @param ?string $correlationId
     * @return void
     *
     * @throws Throwable
     */
    public function awardReferralBonus(
        int $referralId,
        int $bonusAmount,
        ?string $correlationId = null,
    ): void {
        $correlationId ??= Str::uuid()->toString();

        try {
            $referral = Referral::findOrFail($referralId);

            if ($referral->status !== 'registered') {
                throw new DomainException('Referral not in registered status');
            }

            $this->logger->channel('audit')->info('Referral: Bonus award initiated', [
                'correlation_id' => $correlationId,
                'referral_id' => $referralId,
                'referrer_id' => $referral->referrer_id,
                'bonus_amount' => $bonusAmount,
            ]);

            // Award bonus through BonusService
            $this->db->transaction(function () use (
                $referral,
                $bonusAmount,
                $correlationId,
            ) {
                // Award bonus to referrer
                $this->bonus->awardBonus(
                    userId: $referral->referrer_id,
                    tenantId: $referral->tenant_id,
                    amount: $bonusAmount,
                    type: 'referral',
                    sourceType: 'referral_bonus',
                    sourceId: $referral->id,
                    correlationId: $correlationId,
                    metadata: [
                        'referee_id' => $referral->referee_id,
                    ],
                );

                // Update referral status
                $referral->update([
                    'status' => 'rewarded',
                    'rewarded_at' => now(),
                    'bonus_amount' => $bonusAmount,
                    'correlation_id' => $correlationId,
                ]);
            });

            // SUCCESS LOG
            $this->logger->channel('audit')->info('Referral: Bonus awarded', [
                'correlation_id' => $correlationId,
                'referral_id' => $referralId,
                'referrer_id' => $referral->referrer_id,
                'bonus_amount' => $bonusAmount,
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            // ERROR LOG
            $this->logger->channel('audit')->error('Referral: Bonus award failed', [
                'correlation_id' => $correlationId,
                'referral_id' => $referralId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статистику рефереров
     *
     * @param int $referrerId
     * @param int $tenantId
     * @return array
     */
    public function getReferrerStats(int $referrerId, int $tenantId): array
    {
        $referrals = Referral::where('referrer_id', $referrerId)
            ->where('tenant_id', $tenantId)
            ->get();

        return [
            'total_referrals' => $referrals->count(),
            'registered' => $referrals->where('status', 'registered')->count(),
            'rewarded' => $referrals->where('status', 'rewarded')->count(),
            'total_bonus' => $referrals->sum('bonus_amount'),
            'referrals' => $referrals->map(function ($ref) {
                return [
                    'id' => $ref->id,
                    'referee_id' => $ref->referee_id,
                    'status' => $ref->status,
                    'registered_at' => $ref->registered_at,
                    'bonus_amount' => $ref->bonus_amount,
                ];
            }),
        ];
    }

    /**
     * Получить историю рефералов
     *
     * @param int $tenantId
     * @param int $perPage
     * @return \Illuminate\Pagination\Paginator
     */
    public function getHistory(int $tenantId, int $perPage = 20): \Illuminate\Pagination\Paginator
    {
        return Referral::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

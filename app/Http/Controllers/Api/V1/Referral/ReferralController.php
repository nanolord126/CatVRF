declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Referral;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Referral\GenerateReferralRequest;
use App\Http\Requests\Referral\RegisterReferralRequest;
use App\Models\Referral\Referral;
use App\Models\Referral\ReferralReward;
use App\Models\Wallet\Wallet;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Referral Program API Controller.
 * Workflow: Generate link → Register → Qualify (5000₽ spent) → Award bonus.
 *
 * Bonuses:
 * - Referrer: 200₽ (withdrawable)
 * - Referee: Spending-only in platform
 *
 * Migration:
 * - Dikidi: 10% commission (4 months), then 12% (2 years)
 * - Standard: 14% commission
 *
 * Features:
 * - Self-referral prevention
 * - Duplicate prevention
 * - Multi-vertical spending aggregation
 * - 30-day inactivity expiration
 */
final class ReferralController extends BaseApiController
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
    ) {}

    /**
     * POST /api/v1/referral/generate
     * Создать реферальную ссылку.
     *
     * @return JsonResponse
     */
    public function generate(GenerateReferralRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $tenantId = $request->getTenantId();
        $referrerId = auth()->id();

        try {
            return $this->db->transaction(function () use ($referrerId, $correlationId, $tenantId, $request) {
                // Создать referral record
                $referralCode = strtoupper(Str::random(8));

                $referral = Referral::create([
                    'referrer_id' => $referrerId,
                    'referral_code' => $referralCode,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);

                $referralLink = route('referral.register', ['code' => $referralCode]);

                $this->log->channel('audit')->info('Referral link generated', [
                    'correlation_id' => $correlationId,
                    'referrer_id' => $referrerId,
                    'referral_code' => $referralCode,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Referral link generated',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'referral_id' => $referral->id,
                        'code' => $referralCode,
                        'link' => $referralLink,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Referral link generation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Link generation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/referral/register
     * Зарегистрировать приглашённого пользователя.
     *
     * @return JsonResponse
     */
    public function register(RegisterReferralRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $code = $request->input('referral_code');
        $refereeId = auth()->id();

        try {
            return $this->db->transaction(function () use ($code, $refereeId, $correlationId, $request) {
                // 1. Найти реферральный код
                $referral = Referral::where('referral_code', $code)->first();

                if (!$referral) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid referral code',
                        'correlation_id' => $correlationId,
                    ], 404)->send();
                }

                // 2. Проверить самореферал
                if ($referral->referrer_id === $refereeId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot refer yourself',
                        'correlation_id' => $correlationId,
                    ], 400)->send();
                }

                // 3. Проверить дубликаты
                $existingReferral = Referral::where('referrer_id', $referral->referrer_id)
                    ->where('referee_id', $refereeId)
                    ->first();

                if ($existingReferral) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Referral already exists',
                        'correlation_id' => $correlationId,
                    ], 400)->send();
                }

                // 4. Обновить реферральный рекорд
                $referral->update([
                    'referee_id' => $refereeId,
                    'status' => 'registered',
                    'registered_at' => now(),
                    'source_platform' => $request->input('source_platform'),
                    'correlation_id' => $correlationId,
                ]);

                // 5. Fraud check
                $fraudResult = $this->fraudService->checkReferralAbuse(
                    referrer_id: $referral->referrer_id,
                    referee_id: $refereeId,
                    correlation_id: $correlationId,
                );

                if ($fraudResult['decision'] === 'block') {
                    $this->log->channel('fraud_alert')->warning('Referral abuse detected', [
                        'correlation_id' => $correlationId,
                        'referrer_id' => $referral->referrer_id,
                    ]);
                }

                // 6. Логирование
                $this->log->channel('audit')->info('Referral registered', [
                    'correlation_id' => $correlationId,
                    'referral_id' => $referral->id,
                    'referrer_id' => $referral->referrer_id,
                    'referee_id' => $refereeId,
                    'source_platform' => $request->input('source_platform'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Registered successfully',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'referral_id' => $referral->id,
                        'status' => 'registered',
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Referral registration failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/referral/{id}/qualify
     * Проверить квалификацию (5000₽ минимум).
     *
     * @return JsonResponse
     */
    public function qualify(Referral $referral, RegisterReferralRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $qualificationThreshold = 500000; // 5000₽ in kopeks

        try {
            return $this->db->transaction(function () use ($referral, $correlationId, $qualificationThreshold) {
                // 1. Проверить статус
                if ($referral->status !== 'registered') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Referral not in registered state',
                        'correlation_id' => $correlationId,
                    ], 400)->send();
                }

                // 2. Рассчитать общий расход (все вертикали)
                $totalSpending = \App\Models\Payment\PaymentTransaction::where('user_id', $referral->referee_id)
                    ->where('status', 'captured')
                    ->sum('amount');

                // 3. Проверить квалификацию
                if ($totalSpending < $qualificationThreshold) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Spending threshold not met',
                        'correlation_id' => $correlationId,
                        'data' => [
                            'required' => $qualificationThreshold,
                            'current' => $totalSpending,
                        ],
                    ], 400)->send();
                }

                // 4. Обновить статус реферрала
                $referral->update([
                    'status' => 'qualified',
                    'qualified_at' => now(),
                ]);

                // 5. Начислить бонус рефереру (200₽ = 20000 копеек)
                $bonusAmount = 20000;
                
                $referrerWallet = \App\Models\User::find($referral->referrer_id)->wallet 
                    ?? Wallet::factory()->create([
                        'user_id' => $referral->referrer_id,
                    ]);

                $this->walletService->credit(
                    wallet_id: $referrerWallet->id,
                    amount: $bonusAmount,
                    reason: 'Referral reward for ' . $referral->referee_id,
                    correlation_id: $correlationId,
                );

                // 6. Создать ReferralReward рекорд
                ReferralReward::create([
                    'referral_id' => $referral->id,
                    'recipient_type' => 'referrer',
                    'recipient_id' => $referral->referrer_id,
                    'amount' => $bonusAmount,
                    'type' => 'referral_bonus',
                    'status' => 'credited',
                    'credited_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // 7. Логирование
                $this->log->channel('audit')->info('Referral qualified', [
                    'correlation_id' => $correlationId,
                    'referral_id' => $referral->id,
                    'referrer_id' => $referral->referrer_id,
                    'bonus_amount' => $bonusAmount,
                    'total_spending' => $totalSpending,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Referral qualified, bonus awarded',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'referral_id' => $referral->id,
                        'status' => 'qualified',
                        'bonus' => $bonusAmount,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Referral qualification failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Qualification failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/v1/referral/stats
     * Получить статистику рефереров.
     */
    public function stats(RegisterReferralRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $referrerId = auth()->id();

        $referrals = Referral::where('referrer_id', $referrerId)
            ->with('rewards')
            ->get();

        $totalRewards = $referrals->flatMap(fn ($r) => $r->rewards)->sum('amount');
        $qualifiedCount = $referrals->where('status', 'qualified')->count();

        return response()->json([
            'success' => true,
            'correlation_id' => $correlationId,
            'data' => [
                'total_referrals' => $referrals->count(),
                'qualified' => $qualifiedCount,
                'total_rewards' => $totalRewards,
                'referrals' => $referrals->map(fn ($r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'registered_at' => $r->registered_at,
                ]),
            ],
        ], 200);
    }
}

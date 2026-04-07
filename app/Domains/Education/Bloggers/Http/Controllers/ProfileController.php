<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    /**
         * GET /api/profile
         * Get current blogger profile
         */
        public function show(): JsonResponse
        {
            $profile = $request->user()->bloggerProfile;

            return new \Illuminate\Http\JsonResponse([
                'data' => [
                    'id' => $profile->id,
                    'uuid' => $profile->uuid,
                    'display_name' => $profile->display_name,
                    'bio' => $profile->bio,
                    'profile_picture' => $profile->profile_picture,
                    'website' => $profile->website,
                    'category' => $profile->category,
                    'instagram' => $profile->instagram,
                    'tiktok' => $profile->tiktok,
                    'verification_status' => $profile->verification_status,
                    'verified_at' => $profile->verified_at,
                    'rating' => $profile->rating,
                    'total_followers' => $profile->total_followers,
                    'total_streams' => $profile->total_streams,
                    'wallet_balance' => $profile->wallet_balance,
                    'is_featured' => $profile->is_featured,
                    'created_at' => $profile->created_at,
                ],
            ]);
        }

        /**
         * PATCH /api/profile
         * Update profile
         */
        public function update(Request $request): JsonResponse
        {
            $validated = $request->validate([
                'display_name' => 'sometimes|string|max:100',
                'bio' => 'sometimes|string|max:500',
                'category' => 'sometimes|string|in:beauty,fashion,food,travel,fitness,gaming,education,lifestyle,other',
                'website' => 'sometimes|url',
                'instagram' => 'sometimes|url',
                'tiktok' => 'sometimes|url',
                'profile_picture' => 'sometimes|image|max:5120',
            ]);

            $profile = $request->user()->bloggerProfile;

            $this->db->transaction(function () use ($profile, $validated, $request) {
                if ($request->hasFile('profile_picture')) {
                    $path = $request->file('profile_picture')->store('profiles', 'public');
                    $validated['profile_picture'] = $path;
                }

                $profile->update($validated);

                $this->logger->info('Blogger updated profile', [
                    'blogger_id' => $profile->id,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Str::uuid(),
                    'changes' => array_keys($validated),
                ]);
            });

            return new \Illuminate\Http\JsonResponse([
                'message' => 'Профиль обновлён',
                'data' => [
                    'display_name' => $profile->display_name,
                    'bio' => $profile->bio,
                    'profile_picture' => $profile->profile_picture,
                ],
            ]);
        }

        /**
         * PATCH /api/profile/banking
         * Update banking information
         */
        public function updateBanking(Request $request): JsonResponse
        {
            $validated = $request->validate([
                'bank_account' => 'required|string|regex:/^\d{20}$/',
                'bank_name' => 'required|string|max:255',
                'bank_bik' => 'required|string|regex:/^\d{9}$/',
            ]);

            $profile = $request->user()->bloggerProfile;

            $this->db->transaction(function () use ($profile, $validated) {
                $profile->update($validated);

                $this->logger->info('Blogger updated banking info', [
                    'blogger_id' => $profile->id,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Str::uuid(),
                    'bank_last_digits' => substr($validated['bank_account'], -4),
                ]);
            });

            return new \Illuminate\Http\JsonResponse([
                'message' => 'Реквизиты обновлены',
                'bank_account_masked' => str_pad(
                    substr($validated['bank_account'], -4),
                    20,
                    '*',
                    STR_PAD_LEFT
                ),
            ]);
        }

        /**
         * DELETE /api/profile
         * Delete profile (account deactivation)
         */
        public function delete(Request $request): JsonResponse
        {
            $validated = $request->validate([
                'password' => 'required|current_password',
                'reason' => 'nullable|string|max:500',
            ]);

            $profile = $request->user()->bloggerProfile;

            $this->db->transaction(function () use ($profile, $validated) {
                $profile->update([
                    'moderation_status' => 'banned',
                    'deactivated_at' => Carbon::now(),
                ]);

                $request->user()->delete();

                $this->logger->info('Blogger account deactivated', [
                    'blogger_id' => $profile->id,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Str::uuid(),
                    'reason' => $validated['reason'] ?? 'No reason provided',
                ]);
            });

            return new \Illuminate\Http\JsonResponse([
                'message' => 'Аккаунт деактивирован',
            ]);
        }

        /**
         * GET /api/profile/payouts
         * Get payout history
         */
        public function payoutHistory(): JsonResponse
        {
            $profile = $request->user()->bloggerProfile;

            $payouts = $this->db->table('blogger_payouts')
                ->where('blogger_id', $profile->id)
                ->orderByDesc('paid_at')
                ->limit(50)
                ->get();

            return new \Illuminate\Http\JsonResponse([
                'data' => $payouts,
                'summary' => [
                    'total_paid' => $payouts->sum('amount'),
                    'total_pending' => $profile->wallet_balance,
                ],
            ]);
        }

        /**
         * POST /api/profile/request-payout
         * Request payout
         */
        public function requestPayout(Request $request): JsonResponse
        {
            $validated = $request->validate([
                'amount' => 'required|integer|min:50000', // Min 500 rubles
            ]);

            $profile = $request->user()->bloggerProfile;

            if ($validated['amount'] > $profile->wallet_balance) {
                return new \Illuminate\Http\JsonResponse([
                    'error' => 'Недостаточно средств',
                ], 422);
            }

            $this->db->transaction(function () use ($profile, $validated) {
                $profile->decrement('wallet_balance', $validated['amount']);

                $this->db->table('blogger_payouts')->insert([
                    'blogger_id' => $profile->id,
                    'amount' => $validated['amount'],
                    'status' => 'processing',
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Str::uuid(),
                    'created_at' => Carbon::now(),
                ]);

                $this->logger->info('Blogger requested payout', [
                    'blogger_id' => $profile->id,
                    'amount' => $validated['amount'],
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Str::uuid(),
                ]);
            });

            return new \Illuminate\Http\JsonResponse([
                'message' => 'Выплата запрошена',
                'amount' => $validated['amount'],
                'estimated_payment' => Carbon::now()->addDays(4),
            ]);
        }
}

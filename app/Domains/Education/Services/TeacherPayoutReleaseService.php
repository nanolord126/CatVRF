<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class TeacherPayoutReleaseService
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function scheduleTeacherPayout(int $teacherId, int $slotId, int $amountKopecks, string $correlationId): array
    {
        $slot = DB::table('education_slots')->where('id', $slotId)->first();

        if ($slot === null) {
            throw new \DomainException('Slot not found');
        }

        $payoutReleaseTime = $slot->end_time->addHours(24);

        return DB::transaction(function () use ($teacherId, $slotId, $amountKopecks, $payoutReleaseTime, $slot, $correlationId) {
            $payoutId = DB::table('education_teacher_payouts')->insertGetId([
                'id' => (string) Str::uuid(),
                'tenant_id' => $slot->tenant_id,
                'business_group_id' => $slot->business_group_id,
                'teacher_id' => $teacherId,
                'slot_id' => $slotId,
                'amount_kopecks' => $amountKopecks,
                'status' => 'frozen',
                'frozen_at' => now(),
                'scheduled_release_at' => $payoutReleaseTime,
                'released_at' => null,
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record('education_teacher_payout_scheduled', 'TeacherPayout', $payoutId, [], [
                'correlation_id' => $correlationId,
                'teacher_id' => $teacherId,
                'slot_id' => $slotId,
                'amount_rub' => $amountKopecks / 100,
                'scheduled_release_at' => $payoutReleaseTime->toIso8601String(),
            ], $correlationId);

            return [
                'payout_id' => $payoutId,
                'teacher_id' => $teacherId,
                'amount_rub' => $amountKopecks / 100,
                'status' => 'frozen',
                'scheduled_release_at' => $payoutReleaseTime->toIso8601String(),
            ];
        });
    }

    public function releasePayout(int $payoutId, string $correlationId): array
    {
        $payout = DB::table('education_teacher_payouts')->where('id', $payoutId)->first();

        if ($payout === null) {
            throw new \DomainException('Payout not found');
        }

        if ($payout->status !== 'frozen') {
            throw new \DomainException('Payout is not frozen');
        }

        if (now()->lt($payout->scheduled_release_at)) {
            throw new \DomainException('Payout release time has not arrived');
        }

        return DB::transaction(function () use ($payout, $correlationId) {
            DB::table('education_teacher_payouts')
                ->where('id', $payout->id)
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->audit->record('education_teacher_payout_released', 'TeacherPayout', $payout->id, [], [
                'correlation_id' => $correlationId,
                'payout_id' => $payout->id,
                'teacher_id' => $payout->teacher_id,
                'amount_rub' => $payout->amount_kopecks / 100,
            ], $correlationId);

            Log::channel('audit')->info('Teacher payout released', [
                'correlation_id' => $correlationId,
                'payout_id' => $payout->id,
                'teacher_id' => $payout->teacher_id,
            ]);

            return [
                'payout_id' => $payout->id,
                'teacher_id' => $payout->teacher_id,
                'amount_rub' => $payout->amount_kopecks / 100,
                'status' => 'released',
                'released_at' => now()->toIso8601String(),
            ];
        });
    }

    public function processScheduledReleases(string $correlationId): array
    {
        $duePayouts = DB::table('education_teacher_payouts')
            ->where('status', 'frozen')
            ->where('scheduled_release_at', '<=', now())
            ->limit(100)
            ->get();

        $processed = [];

        foreach ($duePayouts as $payout) {
            try {
                $result = $this->releasePayout($payout->id, $correlationId);
                $processed[] = $result;
            } catch (\Exception $e) {
                Log::channel('audit')->error('Failed to release payout', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed_count' => count($processed),
            'payouts' => $processed,
        ];
    }
}

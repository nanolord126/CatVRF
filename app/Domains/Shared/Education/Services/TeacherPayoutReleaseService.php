<?php

declare(strict_types=1);

namespace App\Domains\Education\Services;

use Psr\Log\LoggerInterface;

use App\Services\Fraud\FraudControlService;

use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final readonly class TeacherPayoutReleaseService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControlService,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    public function scheduleTeacherPayout(int $teacherId, int $slotId, int $amountKopecks, string $correlationId): array
    {
        $slot = $this->db->table('education_slots')->where('id', $slotId)->first();

        if ($slot === null) {
            throw new \DomainException('Slot not found');
        }

        $payoutReleaseTime = $slot->end_time->addHours(24);

        return $this->db->transaction(function () use ($teacherId, $slotId, $amountKopecks, $payoutReleaseTime, $slot, $correlationId) {
            $payoutId = $this->db->table('education_teacher_payouts')->insertGetId([
                'id' => (string) Str::uuid(),
                'tenant_id' => $slot->tenant_id,
                'business_group_id' => $slot->business_group_id,
                'teacher_id' => $teacherId,
                'slot_id' => $slotId,
                'amount_kopecks' => $amountKopecks,
                'status' => 'frozen',
                'frozen_at' => CarbonImmutable::now(),
                'scheduled_release_at' => $payoutReleaseTime,
                'released_at' => null,
                'correlation_id' => $correlationId,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
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
        $payout = $this->db->table('education_teacher_payouts')->where('id', $payoutId)->first();

        if ($payout === null) {
            throw new \DomainException('Payout not found');
        }

        if ($payout->status !== 'frozen') {
            throw new \DomainException('Payout is not frozen');
        }

        if (CarbonImmutable::now()->lt($payout->scheduled_release_at)) {
            throw new \DomainException('Payout release time has not arrived');
        }

        return $this->db->transaction(function () use ($payout, $correlationId) {
            $this->db->table('education_teacher_payouts')
                ->where('id', $payout->id)
                ->update([
                    'status' => 'released',
                    'released_at' => CarbonImmutable::now(),
                    'updated_at' => CarbonImmutable::now(),
                ]);

            $this->audit->record('education_teacher_payout_released', 'TeacherPayout', $payout->id, [], [
                'correlation_id' => $correlationId,
                'payout_id' => $payout->id,
                'teacher_id' => $payout->teacher_id,
                'amount_rub' => $payout->amount_kopecks / 100,
            ], $correlationId);

            $this->log->channel('audit')->$this->logger->info('Teacher payout released', [
                'correlation_id' => $correlationId,
                'payout_id' => $payout->id,
                'teacher_id' => $payout->teacher_id,
            ]);

            return [
                'payout_id' => $payout->id,
                'teacher_id' => $payout->teacher_id,
                'amount_rub' => $payout->amount_kopecks / 100,
                'status' => 'released',
                'released_at' => CarbonImmutable::now()->toIso8601String(),
            ];
        });
    }

    public function processScheduledReleases(string $correlationId): array
    {
        $this->fraudControlService->check('process', ['context' => __CLASS__]);
        $duePayouts = $this->db->table('education_teacher_payouts')
            ->where('status', 'frozen')
            ->where('scheduled_release_at', '<=', CarbonImmutable::now())
            ->limit(100)
            ->get();

        $processed = [];

        foreach ($duePayouts as $payout) {
            try {
                $result = $this->releasePayout($payout->id, $correlationId);
                $processed[] = $result;
            } catch (\Exception $e) {
                $this->log->channel('audit')->error('Failed to release payout', [
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

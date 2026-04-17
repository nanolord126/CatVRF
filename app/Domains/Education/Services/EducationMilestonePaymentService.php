<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\Models\Enrollment;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class EducationMilestonePaymentService
{
    private const HOLD_DURATION_HOURS = 4;
    private const MARKETPLACE_COMMISSION_PERCENT = 15;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
    ) {}

    public function createMilestoneSchedule(int $enrollmentId, string $correlationId): array
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $course = $enrollment->course;

        $totalPrice = $enrollment->mode === 'b2b' 
            ? ($course->corporate_price_kopecks ?? $course->price_kopecks)
            : $course->price_kopecks;

        $modules = $course->modules()->orderBy('order')->get();
        $milestoneCount = max($modules->count(), 1);
        $milestoneAmount = (int) ($totalPrice / $milestoneCount);

        $milestones = [];

        DB::transaction(function () use ($enrollment, $modules, $milestoneAmount, &$milestones, $correlationId, $totalPrice) {
            foreach ($modules as $index => $module) {
                $milestone = DB::table('education_payment_milestones')->insertGetId([
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $enrollment->tenant_id,
                    'business_group_id' => $enrollment->business_group_id,
                    'enrollment_id' => $enrollment->id,
                    'user_id' => $enrollment->user_id,
                    'course_id' => $enrollment->course_id,
                    'module_id' => $module->id,
                    'milestone_order' => $index + 1,
                    'amount_kopecks' => $milestoneAmount,
                    'status' => 'pending',
                    'scheduled_for' => null,
                    'paid_at' => null,
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $milestones[] = [
                    'milestone_id' => $milestone,
                    'module_id' => $module->id,
                    'module_title' => $module->title,
                    'amount_rub' => $milestoneAmount / 100,
                    'status' => 'pending',
                ];
            }

            $this->audit->record('education_payment_milestones_created', 'PaymentMilestone', $enrollment->id, [], [
                'correlation_id' => $correlationId,
                'enrollment_id' => $enrollment->id,
                'milestone_count' => count($milestones),
                'total_amount_rub' => $totalPrice / 100,
            ], $correlationId);
        });

        return $milestones;
    }

    public function triggerMilestonePayment(int $moduleId, int $enrollmentId, string $correlationId): array
    {
        $enrollment = DB::table('enrollments')->where('id', $enrollmentId)->first();
        
        $this->fraud->check([
            'operation_type' => 'milestone_payment_trigger',
            'enrollment_id' => $enrollmentId,
            'module_id' => $moduleId,
            'user_id' => $enrollment->user_id,
            'tenant_id' => $enrollment->tenant_id,
            'correlation_id' => $correlationId,
        ]);

        $milestone = DB::table('education_payment_milestones')
            ->where('enrollment_id', $enrollmentId)
            ->where('module_id', $moduleId)
            ->where('status', 'pending')
            ->first();

        if ($milestone === null) {
            throw new \DomainException('No pending milestone found for this module');
        }

        return DB::transaction(function () use ($milestone, $correlationId) {
            $holdExpiry = now()->addHours(self::HOLD_DURATION_HOURS);

            DB::table('education_payment_milestones')
                ->where('id', $milestone->id)
                ->update([
                    'status' => 'held',
                    'scheduled_for' => now(),
                    'hold_expires_at' => $holdExpiry,
                    'updated_at' => now(),
                ]);

            $marketplaceAmount = (int) ($milestone->amount_kopecks * self::MARKETPLACE_COMMISSION_PERCENT / 100);
            $teacherAmount = $milestone->amount_kopecks - $marketplaceAmount;

            DB::table('education_payment_holds')->insert([
                'id' => (string) Str::uuid(),
                'tenant_id' => $milestone->tenant_id,
                'business_group_id' => $milestone->business_group_id,
                'milestone_id' => $milestone->id,
                'user_id' => $milestone->user_id,
                'amount_kopecks' => $milestone->amount_kopecks,
                'marketplace_share_kopecks' => $marketplaceAmount,
                'teacher_share_kopecks' => $teacherAmount,
                'status' => 'held',
                'hold_expires_at' => $holdExpiry,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->audit->record('education_milestone_payment_held', 'PaymentHold', $milestone->id, [], [
                'correlation_id' => $correlationId,
                'milestone_id' => $milestone->id,
                'amount_rub' => $milestone->amount_kopecks / 100,
                'hold_expires_at' => $holdExpiry->toIso8601String(),
                'marketplace_share_rub' => $marketplaceAmount / 100,
                'teacher_share_rub' => $teacherAmount / 100,
            ], $correlationId);

            return [
                'milestone_id' => $milestone->id,
                'amount_rub' => $milestone->amount_kopecks / 100,
                'status' => 'held',
                'hold_expires_at' => $holdExpiry->toIso8601String(),
                'marketplace_share_rub' => $marketplaceAmount / 100,
                'teacher_share_rub' => $teacherAmount / 100,
            ];
        });
    }

    public function processHoldCapture(string $holdId, string $correlationId): array
    {
        $hold = DB::table('education_payment_holds')->where('id', $holdId)->first();

        if ($hold === null) {
            throw new \DomainException('Hold not found');
        }

        if ($hold->status !== 'held') {
            throw new \DomainException('Hold is not in held status');
        }

        if (now()->lt($hold->hold_expires_at)) {
            throw new \DomainException('Hold has not expired yet');
        }

        return DB::transaction(function () use ($hold, $correlationId) {
            DB::table('education_payment_holds')
                ->where('id', $hold->id)
                ->update([
                    'status' => 'captured',
                    'captured_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('education_payment_milestones')
                ->where('id', $hold->milestone_id)
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->audit->record('education_hold_captured', 'PaymentHold', $hold->id, [], [
                'correlation_id' => $correlationId,
                'hold_id' => $hold->id,
                'amount_rub' => $hold->amount_kopecks / 100,
            ], $correlationId);

            return [
                'hold_id' => $hold->id,
                'amount_rub' => $hold->amount_kopecks / 100,
                'status' => 'captured',
            ];
        });
    }

    public function releaseHold(string $holdId, string $reason, string $correlationId): void
    {
        $hold = DB::table('education_payment_holds')->where('id', $holdId)->first();

        if ($hold === null) {
            throw new \DomainException('Hold not found');
        }

        DB::transaction(function () use ($hold, $reason, $correlationId) {
            DB::table('education_payment_holds')
                ->where('id', $hold->id)
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                    'release_reason' => $reason,
                    'updated_at' => now(),
                ]);

            DB::table('education_payment_milestones')
                ->where('id', $hold->milestone_id)
                ->update([
                    'status' => 'pending',
                    'updated_at' => now(),
                ]);

            $this->audit->record('education_hold_released', 'PaymentHold', $hold->id, [], [
                'correlation_id' => $correlationId,
                'hold_id' => $hold->id,
                'reason' => $reason,
            ], $correlationId);
        });
    }
}

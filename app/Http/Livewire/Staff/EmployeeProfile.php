<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * EmployeeProfile — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Профиль сотрудника с доступом к собственным данным
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class EmployeeProfile extends Component
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }
    public array $profile = [];

    public array $performance = [];

    public array $burnout = [];

    public array $achievements = [];

    public array $progress = [];

    public array $shifts = [];

    public array $leaves = [];

    public array $wellness = [];

    public string $activeTab = 'overview';

    public bool $loading = true;

    public bool $showPeerReviewModal = false;

    public array $peerReviewForm = [
        'reviewee_id' => '',
        'rating' => 5,
        'comment' => '',
    ];

    public function mount(CRMStaffIntegrationService $staffService, ?int $employeeId = null): void
    {
        $user = auth()->user();
        $this->profile['employee_id'] = $employeeId ?: $user->id;
        $this->loadProfile($staffService);
        $this->logAction(
            userId: $user?->id,
            tenantId: $user?->tenant_id,
            action: 'profile_viewed',
            entityType: 'employee_profile',
            entityId: $this->profile['employee_id'],
            context: ['viewer_id' => $user->id],
        );
    }

    public function loadProfile(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $this->profile['employee_id'] ?? $user->id;

        $this->loading = true;

        try {
            // Load full profile
            $profileData = $staffService->getEmployeeProfile($tenantId, $employeeId, $user->id);
            $this->profile = array_merge($this->profile, $profileData);

            // Load performance analysis
            $this->performance = $staffService->analyzePerformance($tenantId, $employeeId, $user->id);

            // Load burnout prediction
            $this->burnout = $staffService->predictBurnoutRisk($tenantId, $employeeId, $user->id);

            // Load achievements
            $this->achievements = $staffService->getEmployeeProgress($tenantId, $employeeId, $user->id);

            // Load rank
            $this->progress = $staffService->getEmployeeRank($tenantId, $employeeId, $user->id);

            // Load wellness
            $this->wellness = $staffService->getWorkLifeBalanceScore($tenantId, $employeeId, $user->id);

            // TODO: Load shifts and leaves
            $this->shifts = [];
            $this->leaves = [];
        } catch (\Exception $e) {
            $this->profile = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function submitPeerReview(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $reviewerId = $user->id;
        $revieweeId = (int) $this->peerReviewForm['reviewee_id'];

        $this->validate([
            'peerReviewForm.reviewee_id' => 'required|integer',
            'peerReviewForm.rating' => 'required|integer|min:1|max:5',
            'peerReviewForm.comment' => 'required|string|max:1000',
        ]);

        try {
            $reviewData = [
                'rating' => (int) $this->peerReviewForm['rating'],
                'comment' => $this->peerReviewForm['comment'],
            ];

            $result = $staffService->submitPeerReview($tenantId, $reviewerId, $revieweeId, $reviewData, $user->id);

            $this->showPeerReviewModal = false;
            $this->reset('peerReviewForm');

            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'peer_review_submitted',
                entityType: 'peer_review',
                entityId: $result['review_id'] ?? null,
                context: array_merge($reviewData, ['reviewer_id' => $reviewerId, 'reviewee_id' => $revieweeId]),
            );

            $this->dispatch('peer-review-submitted', reviewId: $result['review_id'] ?? null);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function requestOwnLeave(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        try {
            $leaveData = [
                'type' => 'vacation',
                'start_date' => now()->addWeek(),
                'end_date' => now()->addWeek()->addDays(14),
                'reason' => 'Personal vacation',
            ];

            $result = $staffService->requestLeave($tenantId, $employeeId, $leaveData, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'leave_requested',
                entityType: 'leave',
                entityId: $result['leave_id'] ?? null,
                context: array_merge($leaveData, ['employee_id' => $employeeId]),
            );

            $this->dispatch('leave-requested', leaveId: $result['leave_id'] ?? null);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function clockIn(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        try {
            $locationData = [
                'latitude' => null,
                'longitude' => null,
                'timestamp' => now(),
            ];

            $result = $staffService->clockIn($tenantId, $employeeId, $locationData, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'clocked_in',
                entityType: 'shift',
                entityId: $result['shift_id'] ?? null,
                context: ['employee_id' => $employeeId, 'location' => $locationData],
            );

            $this->dispatch('clocked-in', shiftId: $result['shift_id'] ?? null);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function clockOut(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        try {
            $result = $staffService->clockOut($tenantId, $employeeId, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'clocked_out',
                entityType: 'shift',
                entityId: $result['shift_id'] ?? null,
                context: ['employee_id' => $employeeId],
            );

            $this->dispatch('clocked-out', shiftId: $result['shift_id'] ?? null);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function sendGratitude(int $recipientId, string $message, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $senderId = $user->id;

        try {
            $staffService->sendGratitude($tenantId, $senderId, $recipientId, $message, $user->id);
            $this->dispatch('gratitude-sent', recipientId: $recipientId);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function enrollInCourse(int $courseId, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        try {
            $result = $staffService->enrollEmployeeInCourse($tenantId, $employeeId, $courseId, $user->id);
            $this->dispatch('course-enrolled', enrollmentId: $result['enrollment_id'] ?? null);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openPeerReviewModal(int $revieweeId): void
    {
        $this->peerReviewForm['reviewee_id'] = $revieweeId;
        $this->showPeerReviewModal = true;
    }

    public function closePeerReviewModal(): void
    {
        $this->showPeerReviewModal = false;
        $this->reset('peerReviewForm');
    }

    public function refresh(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadProfile($staffService);
        $this->dispatch('profile-refreshed');
    }

    public function render()
    {
        return view('livewire.staff.employee-profile');
    }
}

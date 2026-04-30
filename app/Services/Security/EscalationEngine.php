<?php

declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use App\Models\ApprovalRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final readonly class EscalationEngine
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly NotificationService $notification,
        private readonly LogManager $log,) {}

    /**
     * Escalate approval
     */
    public function escalate(ApprovalRequest $request): bool
    {
        $escalationLevel = $this->getEscalationLevel($request);
        $recipients = $this->getEscalationRecipients($request);

        if (empty($recipients)) {
            $this->log->warning('No escalation recipients found', [
                'approval_request_id' => $request->id,
                'escalation_level' => $escalationLevel,
            ]);

            return false;
        }

        // Update request
        $request->update([
            'status' => 'escalated',
            'escalation_level' => $escalationLevel,
            'escalated_to_id' => $recipients[0]['id'] ?? null,
            'escalated_at' => CarbonImmutable::now(),
        ]);

        // Send escalation notification
        $this->sendEscalationNotification($request, $recipients);

        $this->log->$this->logger->info('Approval escalated', [
            'approval_request_id' => $request->id,
            'escalation_level' => $escalationLevel,
            'recipients_count' => count($recipients),
        ]);

        return true;
    }

    /**
     * Get escalation level
     */
    public function getEscalationLevel(ApprovalRequest $request): int
    {
        $levels = config('four_eyes.escalation.levels', [
            0 => ['timeout' => 3600, 'escalate_to' => 'manager'],
            1 => ['timeout' => 7200, 'escalate_to' => 'senior_manager'],
            2 => ['timeout' => 14400, 'escalate_to' => 'director'],
        ]);

        $currentLevel = $request->escalation_level ?? 0;
        $maxLevel = count($levels) - 1;

        return min($currentLevel + 1, $maxLevel);
    }

    /**
     * Get escalation recipients
     */
    public function getEscalationRecipients(ApprovalRequest $request): array
    {
        $levels = config('four_eyes.escalation.levels', []);
        $currentLevel = $request->escalation_level ?? 0;

        if (! isset($levels[$currentLevel])) {
            return [];
        }

        $escalateToRole = $levels[$currentLevel]['escalate_to'] ?? null;

        if (! $escalateToRole) {
            return [];
        }

        // Get users with the escalation role in the same tenant
        $recipients = User::where('tenant_id', $request->tenant_id)
            ->where('status', 'active')
            ->whereHas('roles', function ($q) use ($escalateToRole) {
                $q->where('name', $escalateToRole);
            })
            ->get()
            ->toArray();

        return $recipients;
    }

    /**
     * Send escalation notification
     */
    public function sendEscalationNotification(ApprovalRequest $request, array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $this->notification->send(
                userId: $recipient['id'],
                type: 'approval_escalation',
                title: 'Approval Escalation Required',
                message: "Approval request #{$request->request_id} has been escalated and requires your attention.",
                data: [
                    'approval_request_id' => $request->id,
                    'request_id' => $request->request_id,
                    'operation_type' => $request->operation_type,
                    'escalation_level' => $request->escalation_level,
                ],
            );
        }
    }
}

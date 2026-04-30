<?php

declare(strict_types=1);

namespace App\Services\Security;

final readonly class CriticalOperationClassifier
{
    public function __construct() {}

    /**
     * Classify operation
     */
    public function classify(string $operationType, array $context): array
    {
        $isCritical = $this->isCritical($operationType, $context);
        $approvalLevel = $this->getApprovalLevel($operationType, $context);
        $approvalWindow = $this->getApprovalWindow($operationType, $context);
        $requiredApprovers = $this->getRequiredApprovers($operationType, $context);

        return [
            'is_critical' => $isCritical,
            'approval_level' => $approvalLevel,
            'approval_window_seconds' => $approvalWindow,
            'required_approvers' => $requiredApprovers,
        ];
    }

    /**
     * Check if operation matches critical criteria
     */
    public function isCritical(string $operationType, array $context): bool
    {
        $config = config('four_eyes.critical_operations.'.$operationType, null);

        if (! $config || ! ($config['enabled'] ?? false)) {
            return false;
        }

        // Check thresholds
        if (isset($config['thresholds'])) {
            $thresholds = $config['thresholds'];
            $userType = $context['user_type'] ?? 'individual';

            if (isset($thresholds[$userType])) {
                $threshold = $thresholds[$userType];
                $amount = $context['amount'] ?? 0;

                if ($amount >= $threshold) {
                    return true;
                }
            }
        }

        // Check simple threshold
        if (isset($config['threshold'])) {
            $threshold = $config['threshold'];
            $value = $context['value'] ?? $context['count'] ?? 0;

            if ($value >= $threshold) {
                return true;
            }
        }

        // Check target roles
        if (isset($config['target_roles'])) {
            $targetRoles = $config['target_roles'];
            $currentRole = $context['current_role'] ?? null;

            if ($currentRole && in_array($currentRole, $targetRoles, true)) {
                return true;
            }
        }

        // If no specific conditions, operation is critical if enabled
        return isset($config['always_critical']) && $config['always_critical'];
    }

    /**
     * Get required approval level
     */
    public function getApprovalLevel(string $operationType, array $context): string
    {
        $config = config('four_eyes.critical_operations.'.$operationType, []);

        return $config['approval_level'] ?? 'medium';
    }

    /**
     * Get approval window (seconds)
     */
    public function getApprovalWindow(string $operationType, array $context): int
    {
        $config = config('four_eyes.critical_operations.'.$operationType, []);

        return $config['approval_window'] ?? 3600; // Default 1 hour
    }

    /**
     * Get required approver roles
     */
    public function getRequiredApprovers(string $operationType, array $context): array
    {
        $config = config('four_eyes.critical_operations.'.$operationType, []);

        return $config['required_roles'] ?? ['admin'];
    }
}

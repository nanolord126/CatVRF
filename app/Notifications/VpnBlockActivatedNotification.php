<?php

declare(strict_types=1);

namespace App\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * VPN Block Activated Notification
 *
 * Sent to tenant owners and investors when a VPN/proxy block is activated
 * for financial operations or critical changes.
 */
final class VpnBlockActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $riskLevel,
        public readonly string $provider,
        public readonly string $actionType,
        public readonly CarbonImmutable $expiresAt,
        public readonly string $reason,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionTypeLabel = $this->getActionTypeLabel();
        $riskLevelLabel = $this->getRiskLevelLabel();
        $remainingTime = $this->getRemainingTime();

        return (new MailMessage)
            ->subject("⚠️ VPN/Proxy Detected: Financial Operations Blocked")
            ->greeting('Hello ' . ($notifiable->name ?? 'User'))
            ->line('A VPN or proxy connection has been detected on your account, and financial operations have been temporarily blocked as a security measure.')
            ->line('')
            ->line("**Detection Details:**")
            ->line("- **Risk Level:** {$riskLevelLabel}")
            ->line("- **Provider:** {$this->provider}")
            ->line("- **Blocked Action:** {$actionTypeLabel}")
            ->line("- **Block Expires:** {$this->expiresAt->format('Y-m-d H:i:s')} ({$remainingTime})")
            ->line("- **Reason:** {$this->reason}")
            ->line('')
            ->line('**What This Means:**')
            ->line('- Withdrawals and transfers are temporarily disabled')
            ->line('- Bank details changes are blocked')
            ->line('- Critical account changes may be restricted')
            ->line('')
            ->line('**How to Unblock:**')
            ->line('To remove this restriction, you can:')
            ->line('1. Disconnect from the VPN/proxy')
            ->line('2. Wait for the block to expire automatically')
            ->line('3. Complete additional verification (Passkey + liveness check)')
            ->line('')
            ->line('If you believe this is an error or need urgent assistance, please contact our security team.')
            ->action('View Security Status', url('/security/vpn-status'))
            ->line('')
            ->line('Thank you for helping us keep your account secure.')
            ->salutation('CatVRF Security Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'vpn_block_activated',
            'tenant_id' => $this->tenantId,
            'risk_level' => $this->riskLevel,
            'provider' => $this->provider,
            'action_type' => $this->actionType,
            'expires_at' => $this->expiresAt->toIso8601String(),
            'reason' => $this->reason,
            'message' => "Financial operations blocked due to VPN/Proxy detection (Risk: {$this->riskLevel})",
        ];
    }

    /**
     * Get human-readable action type label
     */
    private function getActionTypeLabel(): string
    {
        return match ($this->actionType) {
            'financial_operations' => 'Финансовые операции',
            'critical_changes' => 'Критические изменения',
            'vpn_login' => 'Вход через VPN',
            default => 'Неизвестное действие',
        };
    }

    /**
     * Get human-readable risk level label
     */
    private function getRiskLevelLabel(): string
    {
        return match ($this->riskLevel) {
            'low' => 'Низкий',
            'medium' => 'Средний',
            'high' => 'Высокий',
            'critical' => 'Критический',
            default => ucfirst($this->riskLevel),
        };
    }

    /**
     * Get remaining time in human-readable format
     */
    private function getRemainingTime(): string
    {
        $now = CarbonImmutable::now();
        $diff = $this->expiresAt->diffAsCarbonInterval($now);

        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' ' . $this->pluralize($diff->d, 'день', 'дня', 'дней');
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . ' ' . $this->pluralize($diff->h, 'час', 'часа', 'часов');
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i . ' ' . $this->pluralize($diff->i, 'минута', 'минуты', 'минут');
        }

        return implode(', ', $parts) ?: 'менее минуты';
    }

    /**
     * Russian pluralization helper
     */
    private function pluralize(int $number, string $one, string $two, string $five): string
    {
        $n = abs($number) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $five;
        }

        if ($n1 > 1 && $n1 < 5) {
            return $two;
        }

        if ($n1 === 1) {
            return $one;
        }

        return $five;
    }
}

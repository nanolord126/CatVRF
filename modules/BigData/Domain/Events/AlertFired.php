<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\AlertSeverity;
use Modules\BigData\Domain\ValueObjects\AlertResult;

/**
 * Alert Fired Domain Event
 *
 * Dispatched when a monitoring alert transitions from ok → firing.
 * Carries full alert context for listeners (audit, notifications, self-healing).
 */
final class AlertFired
{
    public readonly CarbonImmutable $occurredAt;

    public function __construct(
        public readonly string $alertName,
        public readonly AlertSeverity $severity,
        public readonly string $message,
        public readonly array $details,
        public readonly ?string $correlationId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? CarbonImmutable::now();
    }

    /**
     * Create from an AlertResult value object
     */
    public static function fromAlertResult(AlertResult $result, ?string $correlationId = null): self
    {
        return new self(
            alertName: $result->name,
            severity: $result->severity,
            message: $result->message,
            details: $result->details,
            correlationId: $correlationId,
        );
    }

    /**
     * Should this event trigger PagerDuty?
     */
    public function shouldPage(): bool
    {
        return $this->severity === AlertSeverity::Critical;
    }

    /**
     * Should this event trigger Slack/Telegram notification?
     */
    public function shouldNotify(): bool
    {
        return in_array($this->severity, [AlertSeverity::Critical, AlertSeverity::Warning], true);
    }

    /**
     * Generate notification text for Slack/Telegram
     */
    public function toNotificationText(): string
    {
        $emoji = $this->severity === AlertSeverity::Critical ? '🚨' : '⚠️';
        $severity = strtoupper($this->severity->value);

        return "{$emoji} [BIGDATA][{$severity}] {$this->alertName}: {$this->message}";
    }

    public function toArray(): array
    {
        return [
            'event' => 'alert_fired',
            'alert_name' => $this->alertName,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'details' => $this->details,
            'correlation_id' => $this->correlationId,
            'occurred_at' => $this->occurredAt->toIso8601String(),
            'should_page' => $this->shouldPage(),
            'should_notify' => $this->shouldNotify(),
        ];
    }
}

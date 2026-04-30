<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\AlertSeverity;

/**
 * Alert Result Value Object
 *
 * Immutable result of an alert evaluation.
 * Domain invariants:
 * - Name must be non-empty
 * - Severity determines whether alert is firing
 * - Message must be non-empty when firing
 * - FiredAt is set automatically when firing
 */
final class AlertResult
{
    public readonly ?CarbonImmutable $firedAt;
    public readonly ?CarbonImmutable $resolvedAt;

    private function __construct(
        public readonly string $name,
        public readonly AlertSeverity $severity,
        public readonly bool $firing,
        public readonly string $message,
        public readonly array $details,
        ?CarbonImmutable $firedAt = null,
        ?CarbonImmutable $resolvedAt = null,
    ) {
        $this->firedAt = $firing ? ($firedAt ?? CarbonImmutable::now()) : null;
        $this->resolvedAt = (!$firing && $resolvedAt !== null) ? $resolvedAt : null;
        $this->validate();
    }

    /**
     * Factory: create a firing alert
     */
    public static function firing(
        string $name,
        AlertSeverity $severity,
        string $message,
        array $details = [],
    ): self {
        if (!$severity->isFiring()) {
            throw new \InvalidArgumentException(
                "Firing alert '{$name}' must have severity > ok, got: {$severity->value}"
            );
        }

        return new self(
            name: $name,
            severity: $severity,
            firing: true,
            message: $message,
            details: $details,
        );
    }

    /**
     * Factory: create a non-firing (ok) result
     */
    public static function ok(string $name, string $message = 'All clear', array $details = []): self
    {
        return new self(
            name: $name,
            severity: AlertSeverity::Ok,
            firing: false,
            message: $message,
            details: $details,
        );
    }

    /**
     * Factory: create from stored alert state
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            severity: AlertSeverity::from($data['severity']),
            firing: $data['firing'],
            message: $data['message'],
            details: $data['details'] ?? [],
            firedAt: isset($data['fired_at']) ? CarbonImmutable::parse($data['fired_at']) : null,
            resolvedAt: isset($data['resolved_at']) ? CarbonImmutable::parse($data['resolved_at']) : null,
        );
    }

    /**
     * Business rule: should this alert be sent to Alertmanager?
     * Only critical and warning alerts are forwarded
     */
    public function shouldNotify(): bool
    {
        return $this->firing && in_array($this->severity, [AlertSeverity::Critical, AlertSeverity::Warning], true);
    }

    /**
     * Business rule: should this alert trigger PagerDuty?
     */
    public function shouldPage(): bool
    {
        return $this->firing && $this->severity === AlertSeverity::Critical;
    }

    /**
     * Business rule: duration since alert fired (seconds)
     */
    public function firingDurationSeconds(): ?float
    {
        if (!$this->firing || $this->firedAt === null) {
            return null;
        }

        return CarbonImmutable::now()->diffInSeconds($this->firedAt, absolute: false);
    }

    /**
     * Business rule: is this alert more severe than another?
     */
    public function isMoreSevereThan(self $other): bool
    {
        return $this->severity->isMoreSevereThan($other->severity);
    }

    /**
     * Business rule: generate a summary for Slack/Telegram notification
     */
    public function toNotificationText(): string
    {
        $emoji = match ($this->severity) {
            AlertSeverity::Critical => '🚨',
            AlertSeverity::Warning => '⚠️',
            AlertSeverity::Info => 'ℹ️',
            default => '✅',
        };

        $severity = strtoupper($this->severity->value);

        return "{$emoji} [{$severity}] {$this->name}: {$this->message}";
    }

    /**
     * Business rule: generate runbook URL from alert name
     */
    public function runbookUrl(): string
    {
        $baseUrl = config('bigdata.monitoring.runbook_base_url', 'https://docs.catvrf.internal/runbook');

        return "{$baseUrl}/" . str_replace('.', '-', strtolower($this->name));
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'severity' => $this->severity->value,
            'firing' => $this->firing,
            'message' => $this->message,
            'details' => $this->details,
            'fired_at' => $this->firedAt?->toIso8601String(),
            'resolved_at' => $this->resolvedAt?->toIso8601String(),
            'should_notify' => $this->shouldNotify(),
            'should_page' => $this->shouldPage(),
            'runbook_url' => $this->runbookUrl(),
        ];
    }

    private function validate(): void
    {
        if (trim($this->name) === '') {
            throw new \InvalidArgumentException('AlertResult name cannot be empty');
        }

        if ($this->firing && trim($this->message) === '') {
            throw new \InvalidArgumentException('AlertResult message cannot be empty when firing');
        }
    }
}

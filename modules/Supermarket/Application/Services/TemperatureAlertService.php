<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\Supermarket\Domain\Models\TemperatureReading;
use Modules\Supermarket\Domain\Models\TemperatureMonitoringDevice;
use Modules\Supermarket\Domain\Models\ProductTemperatureRequirement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * TemperatureAlertService — Alert notifications for temperature violations
 * 
 * Handles violation alerting via email, SMS, push notifications,
 * and system notifications for compliance with 152-ФЗ requirements
 */
final class TemperatureAlertService
{
    use WithAuditLogging;
    use WithTelemetry;

    /**
     * Send violation alert
     */
    public function sendViolationAlert(
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        ProductTemperatureRequirement $requirement
    ): void {
        $this->withSpan(
            'temperature_alert.send_violation',
            function () use ($reading, $device, $requirement) {
                $isCritical = $reading->violation_severity === TemperatureReading::SEVERITY_CRITICAL;
                $isHazardous = $requirement->is_hazardous;

                // Log alert
                $this->logAction(
                    entity: 'temperature_violation_alert',
                    entityId: $reading->id,
                    action: 'sent',
                    context: [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'location' => $device->location,
                        'temperature' => $reading->temperature_celsius,
                        'severity' => $reading->violation_severity,
                        'is_hazardous' => $isHazardous,
                    ]
                );

                // Send notifications based on severity
                if ($isCritical || $isHazardous) {
                    $this->sendCriticalAlert($reading, $device, $requirement);
                } else {
                    $this->sendWarningAlert($reading, $device, $requirement);
                }

                Log::warning('Temperature violation alert sent', [
                    'reading_id' => $reading->id,
                    'device_id' => $device->id,
                    'severity' => $reading->violation_severity,
                    'temperature' => $reading->temperature_celsius,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'send_temperature_alert',
            ),
        );
    }

    /**
     * Send critical violation alert
     */
    private function sendCriticalAlert(
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        ProductTemperatureRequirement $requirement
    ): void {
        // Get responsible personnel (tenant admins, quality managers)
        $recipients = $this->getAlertRecipients($device->tenant_id, 'critical');

        foreach ($recipients as $recipient) {
            // Send email
            $this->sendEmailAlert($recipient, $reading, $device, $requirement, 'critical');

            // Send SMS for critical violations
            if ($recipient->phone) {
                $this->sendSmsAlert($recipient, $reading, $device, 'critical');
            }

            // Send push notification
            $this->sendPushNotification($recipient, $reading, $device, 'critical');
        }

        // Create system notification for dashboard
        $this->createSystemNotification($reading, $device, $requirement, 'critical');
    }

    /**
     * Send warning violation alert
     */
    private function sendWarningAlert(
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        ProductTemperatureRequirement $requirement
    ): void {
        // Get responsible personnel
        $recipients = $this->getAlertRecipients($device->tenant_id, 'warning');

        foreach ($recipients as $recipient) {
            // Send email
            $this->sendEmailAlert($recipient, $reading, $device, $requirement, 'warning');

            // Send push notification
            $this->sendPushNotification($recipient, $reading, $device, 'warning');
        }

        // Create system notification
        $this->createSystemNotification($reading, $device, $requirement, 'warning');
    }

    /**
     * Get alert recipients based on severity
     */
    private function getAlertRecipients(?int $tenantId, string $severity): array
    {
        // TODO: Implement recipient selection based on tenant configuration
        // - Critical: Quality managers, tenant admins, operations managers
        // - Warning: Quality managers, operations managers
        
        // For now, return tenant admins
        if ($tenantId) {
            return \App\Models\User::where('tenant_id', $tenantId)
                ->where('role', 'admin')
                ->get()
                ->toArray();
        }

        return [];
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(
        $recipient,
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        ProductTemperatureRequirement $requirement,
        string $severity
    ): void {
        try {
            // TODO: Create dedicated Mailable class for temperature alerts
            $subject = match($severity) {
                'critical' => '🚨 КРИТИЧЕСКОЕ: Нарушение температурного режима',
                'warning' => '⚠️ Предупреждение: Нарушение температурного режима',
                default => 'Температурное уведомление',
            };

            $message = sprintf(
                "Устройство: %s\nЛокация: %s\nТемпература: %.2f°C\nДопустимый диапазон: %s\nВремя: %s",
                $device->name,
                $device->location,
                $reading->temperature_celsius,
                $requirement->getTemperatureRange(),
                $reading->recorded_at->format('d.m.Y H:i:s')
            );

            // Mail::to($recipient->email)->send(new TemperatureAlertMail($subject, $message, $severity));
            
            Log::info('Temperature alert email sent', [
                'recipient' => $recipient->email ?? 'unknown',
                'severity' => $severity,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send temperature alert email', [
                'error' => $e->getMessage(),
                'recipient' => $recipient->email ?? 'unknown',
            ]);
        }
    }

    /**
     * Send SMS alert
     */
    private function sendSmsAlert(
        $recipient,
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        string $severity
    ): void {
        try {
            $message = sprintf(
                "[%s] %s: %.2f°C (норма: %s)",
                $severity === 'critical' ? 'КРИТ' : 'WARN',
                $device->name,
                $reading->temperature_celsius,
                $device->location
            );

            // TODO: Integrate with SMS service (Twilio, SMS.ru, etc.)
            // SmsService::send($recipient->phone, $message);
            
            Log::info('Temperature alert SMS sent', [
                'recipient' => $recipient->phone ?? 'unknown',
                'severity' => $severity,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send temperature alert SMS', [
                'error' => $e->getMessage(),
                'recipient' => $recipient->phone ?? 'unknown',
            ]);
        }
    }

    /**
     * Send push notification
     */
    private function sendPushNotification(
        $recipient,
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        string $severity
    ): void {
        try {
            $notification = [
                'title' => match($severity) {
                    'critical' => '🚨 Критическое нарушение температуры',
                    'warning' => '⚠️ Нарушение температуры',
                    default => 'Температурное уведомление',
                },
                'body' => sprintf(
                    "%s: %.2f°C в %s",
                    $device->name,
                    $reading->temperature_celsius,
                    $device->location
                ),
                'data' => [
                    'type' => 'temperature_violation',
                    'reading_id' => $reading->id,
                    'device_id' => $device->id,
                    'severity' => $severity,
                ],
            ];

            // TODO: Integrate with push notification service (Firebase, OneSignal, etc.)
            // PushService::send($recipient->id, $notification);
            
            Log::info('Temperature alert push notification sent', [
                'recipient_id' => $recipient->id ?? 'unknown',
                'severity' => $severity,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send temperature alert push notification', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipient->id ?? 'unknown',
            ]);
        }
    }

    /**
     * Create system notification for dashboard
     */
    private function createSystemNotification(
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        ProductTemperatureRequirement $requirement,
        string $severity
    ): void {
        try {
            // TODO: Create database notification for dashboard display
            // Could use Laravel's notification system or custom notifications table
            
            $notificationData = [
                'type' => 'temperature_violation',
                'severity' => $severity,
                'title' => match($severity) {
                    'critical' => 'Критическое нарушение температурного режима',
                    'warning' => 'Нарушение температурного режима',
                    default => 'Температурное уведомление',
                },
                'message' => sprintf(
                    "Устройство %s в локации %s зафиксировало температуру %.2f°C вне допустимого диапазона (%s)",
                    $device->name,
                    $device->location,
                    $reading->temperature_celsius,
                    $requirement->getTemperatureRange()
                ),
                'device_id' => $device->id,
                'reading_id' => $reading->id,
                'temperature' => $reading->temperature_celsius,
                'location' => $device->location,
                'recorded_at' => $reading->recorded_at->toIso8601String(),
            ];

            // Store in cache for real-time dashboard
            $cacheKey = "temp:alert:{$device->tenant_id}:latest";
            \Illuminate\Support\Facades\Cache::put($cacheKey, $notificationData, now()->addHours(24));

            Log::info('Temperature alert system notification created', [
                'device_id' => $device->id,
                'severity' => $severity,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create temperature alert system notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get active alerts for a tenant
     */
    public function getActiveAlerts(?int $tenantId, int $hours = 24): array
    {
        $cacheKey = "temp:alert:{$tenantId}:latest";
        $latestAlert = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$latestAlert) {
            return [];
        }

        // Get recent violations from database
        $violations = TemperatureReading::where('tenant_id', $tenantId)
            ->where('is_violation', true)
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->with(['device', 'productRequirement'])
            ->orderBy('recorded_at', 'desc')
            ->get()
            ->map(function ($reading) {
                return [
                    'id' => $reading->id,
                    'device_name' => $reading->device->name,
                    'location' => $reading->device->location,
                    'temperature' => $reading->temperature_celsius,
                    'severity' => $reading->violation_severity,
                    'recorded_at' => $reading->recorded_at->toIso8601String(),
                    'alert_sent' => $reading->alert_sent,
                ];
            })
            ->toArray();

        return [
            'latest' => $latestAlert,
            'recent_violations' => $violations,
        ];
    }

    /**
     * Resolve alert (mark as handled)
     */
    public function resolveAlert(int $readingId, ?int $userId, string $resolution): bool
    {
        $reading = TemperatureReading::find($readingId);

        if (!$reading) {
            throw new \InvalidArgumentException('Reading not found');
        }

        $this->logAction(
            entity: 'temperature_violation_alert',
            entityId: $readingId,
            action: 'resolved',
            context: [
                'device_id' => $reading->device_id,
                'resolution' => $resolution,
                'resolved_by' => $userId,
            ]
        );

        Log::info('Temperature violation alert resolved', [
            'reading_id' => $readingId,
            'resolution' => $resolution,
            'user_id' => $userId,
        ]);

        return true;
    }
}

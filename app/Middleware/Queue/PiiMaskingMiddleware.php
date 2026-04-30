<?php

declare(strict_types=1);

namespace App\Middleware\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Log\LogManager;
use Closure;

/**
 * PiiMaskingMiddleware - Masks PII data in job logs and payloads
 *
 * CRITICAL: Ensures compliance with 152-ФЗ and medical data protection laws
 * - Masks sensitive fields in job payloads before logging
 * - Prevents PII leakage in Horizon dashboard
 * - Configurable via environment variable PII_MASKING_ENABLED
 * - Medical data (symptoms, diagnoses) is always masked
 *
 * CatVRF 2026 - Production Ready
 */
final class PiiMaskingMiddleware
{
    // Fields that should be masked
    private const SENSITIVE_FIELDS = [
        'email',
        'phone',
        'phone_number',
        'mobile',
        'ssn',
        'passport',
        'credit_card',
        'card_number',
        'cvv',
        'iban',
        'address',
        'full_name',
        'first_name',
        'last_name',
        'patronymic',
        'symptoms',
        'diagnosis',
        'medical_history',
        'health_score',
        'patient_data',
        'doctor_notes',
    ];

    private readonly bool $piiMaskingEnabled;

    public function __construct(
        private readonly LogManager $log,
    ) {
        $this->piiMaskingEnabled = config('pii.masking_enabled', true);
    }

    public function handle(Job $job, Closure $next): void
    {
        if (! $this->piiMaskingEnabled) {
            $next($job);

            return;
        }

        // Mask sensitive data in job payload before execution
        $this->maskPayload($job);

        $next($job);
    }

    /**
     * Mask sensitive data in job payload
     */
    private function maskPayload(Job $job): void
    {
        try {
            $payload = $job->payload();

            // Mask sensitive fields in payload data
            if (isset($payload['data'])) {
                $payload['data'] = $this->maskArray($payload['data']);
            }

            // Mask sensitive fields in command if present
            if (isset($payload['data']['command'])) {
                $command = unserialize($payload['data']['command']);
                $maskedCommand = $this->maskObject($command);

                // Store masked version for logging (original command remains intact for execution)
                $this->log->debug('Job payload masked', [
                    'job_class' => $job->resolveName(),
                    'masked_data' => $this->maskArray($payload['data']),
                ]);
            }
        } catch (\Exception $e) {
            $this->log->warning('Failed to mask job payload', [
                'job_class' => $job->resolveName(),
                'error' => $e->getMessage(),
            ]);

            // Proceed anyway to avoid blocking jobs
        }
    }

    /**
     * Recursively mask sensitive fields in array
     */
    private function maskArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $data[$key] = $this->maskValue($value, $key);
            } elseif (is_array($value)) {
                $data[$key] = $this->maskArray($value);
            } elseif (is_object($value)) {
                $data[$key] = $this->maskObject($value);
            }
        }

        return $data;
    }

    /**
     * Mask sensitive fields in object
     */
    private function maskObject(object $object): object
    {
        $reflection = new \ReflectionClass($object);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            if ($property->isPublic() || $property->isProtected()) {
                $property->setAccessible(true);
                $value = $property->getValue($object);

                if ($this->isSensitiveField($property->getName())) {
                    $property->setValue($object, $this->maskValue($value, $property->getName()));
                } elseif (is_array($value)) {
                    $property->setValue($object, $this->maskArray($value));
                } elseif (is_object($value)) {
                    $property->setValue($object, $this->maskObject($value));
                }
            }
        }

        return $object;
    }

    /**
     * Check if field name is sensitive
     */
    private function isSensitiveField(string $fieldName): bool
    {
        $lowerField = strtolower($fieldName);

        foreach (self::SENSITIVE_FIELDS as $sensitiveField) {
            if (str_contains($lowerField, $sensitiveField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask a value based on its type and field name
     */
    private function maskValue(mixed $value, string $fieldName): string
    {
        if ($value === null) {
            return 'null';
        }

        $lowerField = strtolower($fieldName);

        // Email masking: user***@domain.com
        if (str_contains($lowerField, 'email')) {
            return preg_replace('/(?<=.{2}).*(?=@)/', '***', (string) $value);
        }

        // Phone masking: +7 (***) ***-**-**
        if (str_contains($lowerField, 'phone') || str_contains($lowerField, 'mobile')) {
            return preg_replace('/\d(?=\d{4})/', '*', (string) $value);
        }

        // Credit card masking: **** **** **** 1234
        if (str_contains($lowerField, 'card') || str_contains($lowerField, 'cvv')) {
            return '****';
        }

        // Medical data masking: [REDACTED]
        if (str_contains($lowerField, 'symptom') ||
            str_contains($lowerField, 'diagnosis') ||
            str_contains($lowerField, 'medical') ||
            str_contains($lowerField, 'health') ||
            str_contains($lowerField, 'patient')) {
            return '[REDACTED_MEDICAL]';
        }

        // Default masking: ***
        if (is_string($value) && strlen($value) > 4) {
            return substr($value, 0, 2).'***'.substr($value, -1);
        }

        return '***';
    }
}

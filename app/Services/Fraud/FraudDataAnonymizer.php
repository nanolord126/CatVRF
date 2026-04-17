<?php declare(strict_types=1);

namespace App\Services\Fraud;

use Illuminate\Log\LogManager;

/**
 * Fraud Data Anonymizer for PII Protection
 * 
 * Anonymizes sensitive data (especially medical) before fraud logging
 * Compliance with 152-ФЗ, ФЗ-323
 */
final readonly class FraudDataAnonymizer
{
    private const MASK_PATTERN = '***';
    private const MIN_PRESERVE_LENGTH = 2;

    public function __construct(
        private readonly LogManager $logger,
    ) {}

    /**
     * Anonymize operation context before fraud check
     * Removes or masks PII, medical symptoms, lab results
     */
    public function anonymizeContext(array $context, string $operationType): array
    {
        $anonymized = $context;

        // Medical operations require strict anonymization
        if ($this->isMedicalOperation($operationType)) {
            $anonymized = $this->anonymizeMedicalData($anonymized);
        }

        // Always anonymize common PII fields
        $anonymized = $this->anonymizeCommonPII($anonymized);

        return $anonymized;
    }

    /**
     * Check if operation is medical-related
     */
    private function isMedicalOperation(string $operationType): bool
    {
        $medicalOperations = [
            'ai_diagnosis',
            'predict_health_score',
            'recommend_doctors',
            'hold_appointment_slot',
            'confirm_appointment',
            'medical_record_create',
            'medical_record_update',
            'lab_result_upload',
            'symptom_check',
        ];

        return in_array($operationType, $medicalOperations, true);
    }

    /**
     * Anonymize medical-specific data
     */
    private function anonymizeMedicalData(array $data): array
    {
        $medicalFields = [
            'symptoms',
            'diagnosis',
            'lab_results',
            'medical_history',
            'prescriptions',
            'allergies',
            'chronic_conditions',
            'vital_signs',
            'patient_notes',
            'doctor_notes',
        ];

        foreach ($medicalFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->maskSensitiveValue($data[$field]);
            }
        }

        // Anonymize nested medical data in context
        if (isset($data['context']) && is_array($data['context'])) {
            $data['context'] = $this->anonymizeMedicalData($data['context']);
        }

        return $data;
    }

    /**
     * Anonymize common PII fields
     */
    private function anonymizeCommonPII(array $data): array
    {
        $piiFields = [
            'email',
            'phone',
            'full_name',
            'first_name',
            'last_name',
            'middle_name',
            'address',
            'passport',
            'snils',
            'inn',
            'credit_card',
            'bank_account',
        ];

        foreach ($piiFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->maskPIIValue($data[$field], $field);
            }
        }

        return $data;
    }

    /**
     * Mask sensitive value completely
     */
    private function maskSensitiveValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => self::MASK_PATTERN, $value);
        }

        if (is_string($value)) {
            return self::MASK_PATTERN;
        }

        return null;
    }

    /**
     * Mask PII value preserving some characters for debugging
     */
    private function maskPIIValue(mixed $value, string $fieldType): mixed
    {
        if (!is_string($value)) {
            return self::MASK_PATTERN;
        }

        $length = strlen($value);
        
        if ($length <= self::MIN_PRESERVE_LENGTH) {
            return self::MASK_PATTERN;
        }

        return match ($fieldType) {
            'email' => $this->maskEmail($value),
            'phone' => $this->maskPhone($value),
            'credit_card' => $this->maskCreditCard($value),
            default => $this->maskGeneric($value),
        };
    }

    /**
     * Mask email preserving domain
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return self::MASK_PATTERN;
        }

        $local = $parts[0];
        $domain = $parts[1];

        $maskedLocal = strlen($local) > 2 
            ? substr($local, 0, 1) . str_repeat('*', strlen($local) - 2) . substr($local, -1)
            : str_repeat('*', strlen($local));

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Mask phone preserving last 4 digits
     */
    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($digits);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($digits, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }

    /**
     * Mask credit card showing only last 4
     */
    private function maskCreditCard(string $card): string
    {
        $digits = preg_replace('/[^0-9]/', '', $card);
        $length = strlen($digits);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($digits, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked;
    }

    /**
     * Generic mask preserving first and last character
     */
    private function maskGeneric(string $value): string
    {
        $length = strlen($value);
        
        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 1) . str_repeat('*', $length - 2) . substr($value, -1);
    }

    /**
     * Validate that data is properly anonymized
     */
    public function validateAnonymization(array $original, array $anonymized): bool
    {
        $sensitiveKeywords = [
            'symptom', 'diagnosis', 'lab', 'medical', 'prescription',
            'allergy', 'chronic', 'vital', 'patient', 'doctor',
            'email', 'phone', 'name', 'address', 'passport', 'snils',
        ];

        $flattened = $this->flattenArray($anonymized);

        foreach ($flattened as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            foreach ($sensitiveKeywords as $keyword) {
                if (stripos($key, $keyword) !== false && !str_contains($value, self::MASK_PATTERN)) {
                    $this->logger->channel('fraud_alert')->warning('Potential PII leak in anonymized data', [
                        'key' => $key,
                        'value_length' => strlen($value),
                    ]);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Flatten nested array for validation
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}

<?php declare(strict_types=1);

namespace App\Services\Logging;

use Illuminate\Support\Str;

/**
 * Sensitive Data Masker
 * 
 * Masks sensitive data in logs to prevent PII/PCI exposure.
 * Handles:
 * - Credit card numbers
 * - Bank account numbers
 * - Email addresses
 * - Phone numbers
 * - User IDs
 * - Payment amounts (partial masking)
 */
final readonly class SensitiveDataMasker
{
    /**
     * Mask sensitive data in log context
     * 
     * @param array $context Log context
     * @return array Sanitized context
     */
    public function maskContext(array $context): array
    {
        return collect($context)->map(function ($value, $key) {
            if (is_array($value)) {
                return $this->maskContext($value);
            }

            return match(true) {
                $this->isCreditCardField($key) => $this->maskCreditCard((string) $value),
                $this->isBankAccountField($key) => $this->maskBankAccount((string) $value),
                $this->isEmailField($key) => $this->maskEmail((string) $value),
                $this->isPhoneField($key) => $this->maskPhone((string) $value),
                $this->isUserIdField($key) => $this->maskUserId((string) $value),
                $this->isAmountField($key) => $this->maskAmount((string) $value),
                default => $value,
            };
        })->all();
    }

    /**
     * Mask credit card number (show last 4 digits)
     */
    private function maskCreditCard(string $value): string
    {
        $cleaned = preg_replace('/\D/', '', $value);
        if (strlen($cleaned) < 4) {
            return '****';
        }

        $lastFour = substr($cleaned, -4);
        return str_repeat('*', strlen($cleaned) - 4) . $lastFour;
    }

    /**
     * Mask bank account number
     */
    private function maskBankAccount(string $value): string
    {
        $cleaned = preg_replace('/\D/', '', $value);
        if (strlen($cleaned) < 4) {
            return '****';
        }

        $lastFour = substr($cleaned, -4);
        return '****' . $lastFour;
    }

    /**
     * Mask email address
     */
    private function maskEmail(string $value): string
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }

        [$local, $domain] = explode('@', $value);
        $maskedLocal = substr($local, 0, 2) . str_repeat('*', strlen($local) - 2);
        
        return $maskedLocal . '@' . $domain;
    }

    /**
     * Mask phone number
     */
    private function maskPhone(string $value): string
    {
        $cleaned = preg_replace('/\D/', '', $value);
        if (strlen($cleaned) < 4) {
            return '****';
        }

        $lastFour = substr($cleaned, -4);
        return '+' . str_repeat('*', strlen($cleaned) - 4) . $lastFour;
    }

    /**
     * Mask user ID
     */
    private function maskUserId(string $value): string
    {
        if (strlen($value) < 4) {
            return '***';
        }

        $lastTwo = substr($value, -2);
        return str_repeat('*', strlen($value) - 2) . $lastTwo;
    }

    /**
     * Mask amount (show range)
     */
    private function maskAmount(string $value): string
    {
        $amount = (int) $value;
        
        if ($amount < 1000) {
            return '<1000';
        } elseif ($amount < 10000) {
            return '1000-9999';
        } elseif ($amount < 100000) {
            return '10k-99k';
        } elseif ($amount < 1000000) {
            return '100k-999k';
        } else {
            return '1M+';
        }
    }

    /**
     * Check if field is a credit card field
     */
    private function isCreditCardField(string $key): bool
    {
        $patterns = ['card', 'pan', 'credit', 'cvv', 'cvc'];
        return $this->matchesPattern($key, $patterns);
    }

    /**
     * Check if field is a bank account field
     */
    private function isBankAccountField(string $key): bool
    {
        $patterns = ['account', 'iban', 'bic', 'swift', 'bank'];
        return $this->matchesPattern($key, $patterns);
    }

    /**
     * Check if field is an email field
     */
    private function isEmailField(string $key): bool
    {
        $patterns = ['email', 'mail'];
        return $this->matchesPattern($key, $patterns);
    }

    /**
     * Check if field is a phone field
     */
    private function isPhoneField(string $key): bool
    {
        $patterns = ['phone', 'mobile', 'tel'];
        return $this->matchesPattern($key, $patterns);
    }

    /**
     * Check if field is a user ID field
     */
    private function isUserIdField(string $key): bool
    {
        $patterns = ['user_id', 'customer_id', 'client_id', 'tenant_id'];
        return $this->matchesPattern($key, $patterns);
    }

    /**
     * Check if field is an amount field
     */
    private function isAmountField(string $key): bool
    {
        $patterns = ['amount', 'price', 'sum', 'total', 'balance'];
        return $this->matchesPattern($key, $patterns);
    }

    /**
     * Check if key matches any pattern
     */
    private function matchesPattern(string $key, array $patterns): bool
    {
        $lowerKey = strtolower($key);
        
        foreach ($patterns as $pattern) {
            if (str_contains($lowerKey, $pattern)) {
                return true;
            }
        }

        return false;
    }
}

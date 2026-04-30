<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Masked Cast for Personal Data (152-FZ Compliance)
 * 
 * Provides data masking for staff access to personal data.
 * Ensures that sensitive data is never displayed in plain text to unauthorized users.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * ФСТЭК №21: Мера 2 - Разграничение доступа
 */
final readonly class MaskedCast implements CastsAttributes
{
    private const MASK_CHAR = '*';

    public function __construct(
        private readonly ?string $maskType = null,
    ) {
        $this->maskType = $maskType ?? 'default';
    }

    /**
     * Mask the value when retrieving from database
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If user is accessing their own data, return unmasked
        if (auth()->check() && auth()->id() === $model->getAttribute('user_id')) {
            return $value;
        }

        // If user has admin role with JIT access, return unmasked
        if ($this->hasJitAccess()) {
            return $value;
        }

        // Otherwise, mask the data
        return $this->maskValue($value, $this->maskType);
    }

    /**
     * Store the value as-is in database
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    /**
     * Mask value based on type
     */
    private function maskValue(string $value, string $maskType): string
    {
        return match ($maskType) {
            'email' => $this->maskEmail($value),
            'phone' => $this->maskPhone($value),
            'inn' => $this->maskInn($value),
            'passport' => $this->maskPassport($value),
            'name' => $this->maskName($value),
            'default' => $this->maskDefault($value),
        };
    }

    /**
     * Mask email: a***@example.com
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $this->maskDefault($email);
        }

        [$local, $domain] = $parts;
        $maskedLocal = strlen($local) > 0 ? $local[0] . str_repeat(self::MASK_CHAR, strlen($local) - 1) : '';

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Mask phone: +7 (9**) ***-**-**
     */
    private function maskPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($cleaned) < 10) {
            return $this->maskDefault($phone);
        }

        // Russian format: +7 (XXX) XXX-XX-XX
        $masked = '+7 (' . substr($cleaned, 1, 1) . '**) ***-**-**';
        
        return $masked;
    }

    /**
     * Mask INN: 1234567890** (last 2 digits masked)
     */
    private function maskInn(string $inn): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $inn);
        
        if (strlen($cleaned) < 2) {
            return $this->maskDefault($inn);
        }

        $visibleLength = max(2, strlen($cleaned) - 2);
        $masked = substr($cleaned, 0, $visibleLength) . str_repeat(self::MASK_CHAR, 2);

        return $masked;
    }

    /**
     * Mask passport: 12** 567890
     */
    private function maskPassport(string $passport): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $passport);
        
        if (strlen($cleaned) < 4) {
            return $this->maskDefault($passport);
        }

        // Series: first 2 digits, Number: rest
        $series = substr($cleaned, 0, 2) . '**';
        $number = substr($cleaned, 4);

        return $series . ' ' . $number;
    }

    /**
     * Mask name: А***в
     */
    private function maskName(string $name): string
    {
        $trimmed = trim($name);
        
        if (strlen($trimmed) <= 2) {
            return $this->maskDefault($trimmed);
        }

        $firstChar = mb_substr($trimmed, 0, 1);
        $lastChar = mb_substr($trimmed, -1);
        $middleLength = mb_strlen($trimmed) - 2;

        return $firstChar . str_repeat(self::MASK_CHAR, $middleLength) . $lastChar;
    }

    /**
     * Default mask: show first 2 and last 2 characters
     */
    private function maskDefault(string $value): string
    {
        if (strlen($value) <= 4) {
            return str_repeat(self::MASK_CHAR, strlen($value));
        }

        $start = substr($value, 0, 2);
        $end = substr($value, -2);
        $middle = str_repeat(self::MASK_CHAR, strlen($value) - 4);

        return $start . $middle . $end;
    }

    /**
     * Check if user has JIT access for unmasked data
     */
    private function hasJitAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Check if user has admin role
        if (! $user->hasRole(['admin', 'security_admin', 'compliance_officer'])) {
            return false;
        }

        // Check if user has active JIT access
        return cache()->get("jit_access:{$user->id}", false);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class PiiGuardMiddleware
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    /**
     * Fields that contain PII and should be masked
     */
    private const array PII_FIELDS = [
        'email',
        'phone',
        'phone_number',
        'mobile',
        'ssn',
        'social_security_number',
        'passport',
        'passport_number',
        'id_number',
        'address',
        'full_address',
        'credit_card',
        'card_number',
        'iban',
        'bank_account',
        'medical_record_number',
        'patient_id',
        'diagnosis',
        'symptoms',
        'medical_history',
        'health_data',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        // Only mask JSON responses
        if (!($response instanceof JsonResponse)) {
            return $response;
        }

        $originalContent = $response->getData(true);
        $maskedContent = $this->maskPii($originalContent);

        if ($maskedContent !== $originalContent) {
            $this->log->warning('PII data masked in API response', [
                'path' => $request->path(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
            ]);

            $response->setData($maskedContent);
        }

        return $response;
    }

    /**
     * Recursively mask PII fields in data
     */
    private function maskPii(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $masked = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (in_array($lowerKey, self::PII_FIELDS, true)) {
                $masked[$key] = $this->maskValue($value, $lowerKey);
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskPii($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Mask a PII value based on its type
     */
    private function maskValue(mixed $value, string $fieldType): string
    {
        if (!is_string($value)) {
            return '[REDACTED]';
        }

        $length = strlen($value);

        // Email: show first 2 chars, mask rest, show domain
        if (str_contains($fieldType, 'email')) {
            $parts = explode('@', $value);
            if (count($parts) === 2) {
                $local = $parts[0];
                $domain = $parts[1];
                $maskedLocal = strlen($local) > 2 ? substr($local, 0, 2) . str_repeat('*', strlen($local) - 2) : '**';
                return $maskedLocal . '@' . $domain;
            }
        }

        // Phone: show first 3 and last 2 digits
        if (str_contains($fieldType, 'phone')) {
            if ($length > 5) {
                return substr($value, 0, 3) . str_repeat('*', $length - 5) . substr($value, -2);
            }
            return str_repeat('*', $length);
        }

        // Credit card: show first 4 and last 4
        if (str_contains($fieldType, 'card') || str_contains($fieldType, 'credit')) {
            if ($length > 8) {
                return substr($value, 0, 4) . str_repeat('*', $length - 8) . substr($value, -4);
            }
            return str_repeat('*', $length);
        }

        // Default: show first 2 chars
        if ($length > 2) {
            return substr($value, 0, 2) . str_repeat('*', $length - 2);
        }

        return '**';
    }
}

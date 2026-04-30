<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum IoTSecurityEventType: string
{
    case AUTHENTICATION_SUCCESS = 'authentication_success';
    case AUTHENTICATION_FAILURE = 'authentication_failure';
    case SIGNATURE_VERIFICATION_FAILED = 'signature_verification_failed';
    case CERTIFICATE_EXPIRED = 'certificate_expired';
    case CERTIFICATE_REVOKED = 'certificate_revoked';
    case TOKEN_EXPIRED = 'token_expired';
    case TOKEN_REVOKED = 'token_revoked';
    case RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    case ANOMALY_DETECTED = 'anomaly_detected';
    case QUARANTINE_TRIGGERED = 'quarantine_triggered';
    case QUARANTINE_LIFTED = 'quarantine_lifted';
    case MITM_ATTEMPT = 'mitm_attempt';
    case REPLAY_ATTACK = 'replay_attack';
    case DEVICE_SPOOFING = 'device_spoofing';
    case UNAUTHORIZED_ACCESS = 'unauthorized_access';
    case POLICY_VIOLATION = 'policy_violation';

    public function getSeverity(): string
    {
        return match ($this) {
            self::AUTHENTICATION_SUCCESS,
            self::QUARANTINE_LIFTED => 'info',
            
            self::CERTIFICATE_EXPIRED,
            self::TOKEN_EXPIRED,
            self::RATE_LIMIT_EXCEEDED,
            self::POLICY_VIOLATION => 'warning',
            
            self::AUTHENTICATION_FAILURE,
            self::SIGNATURE_VERIFICATION_FAILED,
            self::CERTIFICATE_REVOKED,
            self::TOKEN_REVOKED,
            self::ANOMALY_DETECTED,
            self::QUARANTINE_TRIGGERED => 'critical',
            
            self::MITM_ATTEMPT,
            self::REPLAY_ATTACK,
            self::DEVICE_SPOOFING,
            self::UNAUTHORIZED_ACCESS => 'emergency',
        };
    }

    public function requiresQuarantine(): bool
    {
        return match ($this) {
            self::MITM_ATTEMPT,
            self::REPLAY_ATTACK,
            self::DEVICE_SPOOFING,
            self::UNAUTHORIZED_ACCESS,
            self::SIGNATURE_VERIFICATION_FAILED => true,
            default => false,
        };
    }
}

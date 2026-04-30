<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Enums;

/**
 * BigData Security Enumerations
 *
 * Domain enums for the security & compliance subsystem.
 * Covers data classification, threat levels, compliance status,
 * and retention categories for GDPR/152-FZ/PCI-DSS.
 */

// ============================================================================
// Data Classification Level (Zero Trust labeling)
// ============================================================================

enum DataClassification: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Confidential = 'confidential';
    case Restricted = 'restricted';
    case TopSecret = 'top_secret';

    /**
     * Can this classification be sent to external LLM / AI?
     */
    public function allowsExternalProcessing(): bool
    {
        return $this === self::Public;
    }

    /**
     * Does this classification require field-level encryption?
     */
    public function requiresEncryption(): bool
    {
        return in_array($this, [self::Confidential, self::Restricted, self::TopSecret], true);
    }

    /**
     * Does this classification require PII masking in logs?
     */
    public function requiresMasking(): bool
    {
        return in_array($this, [self::Internal, self::Confidential, self::Restricted, self::TopSecret], true);
    }

    /**
     * Does this classification require audit logging for every access?
     */
    public function requiresAuditLog(): bool
    {
        return $this !== self::Public;
    }

    /**
     * Map to ClickHouse column encryption tier
     */
    public function toEncryptionTier(): string
    {
        return match ($this) {
            self::Public => 'none',
            self::Internal => 'aes128',
            self::Confidential, self::Restricted => 'aes256',
            self::TopSecret => 'aes256_envelope',
        };
    }

    /**
     * Map to compliance labels
     * @return array<string>
     */
    public function complianceLabels(): array
    {
        return match ($this) {
            self::Public => [],
            self::Internal => ['gdpr:processed'],
            self::Confidential => ['gdpr:pii', '152fz:personal'],
            self::Restricted => ['gdpr:pii', '152fz:sensitive', 'pci:dss'],
            self::TopSecret => ['gdpr:special_category', '152fz:health', 'pci:dss:cardholder'],
        };
    }

    /**
     * Determine classification from data category
     */
    public static function fromDataCategory(DataCategory $category): self
    {
        return match ($category) {
            DataCategory::RawEvent => self::Internal,
            DataCategory::SellerMetric => self::Confidential,
            DataCategory::CLVPrediction => self::Restricted,
            DataCategory::BuyerFeature => self::Restricted,
            DataCategory::ABTestResult => self::Internal,
            DataCategory::PaymentToken => self::TopSecret,
            DataCategory::MedicalRecord => self::TopSecret,
            DataCategory::AuditLog => self::Confidential,
        };
    }
}

// ============================================================================
// Data Category (maps to ClickHouse tables)
// ============================================================================

enum DataCategory: string
{
    case RawEvent = 'raw_event';
    case SellerMetric = 'seller_metric';
    case CLVPrediction = 'clv_prediction';
    case BuyerFeature = 'buyer_feature';
    case ABTestResult = 'abtest_result';
    case PaymentToken = 'payment_token';
    case MedicalRecord = 'medical_record';
    case AuditLog = 'audit_log';

    /**
     * Map to ClickHouse table name
     */
    public function toTableName(): string
    {
        return match ($this) {
            self::RawEvent => 'ch_raw_events',
            self::SellerMetric => 'ch_seller_daily_metrics',
            self::CLVPrediction => 'ch_clv_predictions',
            self::BuyerFeature => 'ch_buyer_seller_features',
            self::ABTestResult => 'ch_abtest_results',
            self::PaymentToken => 'ch_payment_tokens',
            self::MedicalRecord => 'ch_medical_records',
            self::AuditLog => 'ch_audit_log',
        };
    }

    /**
     * PII fields that must be masked/anonymized for this category
     * @return array<string>
     */
    public function piiFields(): array
    {
        return match ($this) {
            self::RawEvent => ['user_id', 'ip_address', 'session_id', 'device_id'],
            self::SellerMetric => ['seller_email', 'seller_phone', 'bank_account'],
            self::CLVPrediction => ['user_id', 'email', 'phone'],
            self::BuyerFeature => ['user_id', 'email', 'phone', 'ip_address', 'device_id'],
            self::ABTestResult => ['user_id', 'session_id'],
            self::PaymentToken => ['card_token', 'card_last4', 'card_brand', 'fingerprint'],
            self::MedicalRecord => ['patient_name', 'diagnosis', 'prescription', 'health_score'],
            self::AuditLog => ['ip_address', 'user_agent', 'device_fingerprint'],
        };
    }

    /**
     * Retention in days per compliance requirements
     */
    public function retentionDays(): int
    {
        return match ($this) {
            self::RawEvent => 30,
            self::SellerMetric => 1095, // 3 years
            self::CLVPrediction => 365,
            self::BuyerFeature => 90,
            self::ABTestResult => 365,
            self::PaymentToken => 90, // PCI-DSS: 90 days for tokens
            self::MedicalRecord => 1825, // 5 years (ФЗ-323)
            self::AuditLog => 1095, // 3 years immutable
        };
    }

    /**
     * Does this category support GDPR Right to be Forgotten?
     */
    public function supportsGDPRDeletion(): bool
    {
        return in_array($this, [
            self::RawEvent,
            self::CLVPrediction,
            self::BuyerFeature,
            self::ABTestResult,
            self::PaymentToken,
        ], true);
    }

    /**
     * All categories that contain PII
     * @return array<self>
     */
    public static function piiCategories(): array
    {
        return array_filter(self::cases(), fn(self $c) => !empty($c->piiFields()));
    }
}

// ============================================================================
// Threat Level
// ============================================================================

enum ThreatLevel: int
{
    case None = 0;
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;

    public function isActionable(): bool
    {
        return $this->value >= self::Medium->value;
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    /**
     * From anomaly score (0.0 - 1.0)
     */
    public static function fromAnomalyScore(float $score): self
    {
        return match (true) {
            $score < 0.2 => self::None,
            $score < 0.4 => self::Low,
            $score < 0.6 => self::Medium,
            $score < 0.8 => self::High,
            default => self::Critical,
        };
    }

    /**
     * From query pattern deviation (standard deviations from mean)
     */
    public static function fromQueryDeviation(float $sigma): self
    {
        return match (true) {
            $sigma < 1.5 => self::None,
            $sigma < 2.0 => self::Low,
            $sigma < 3.0 => self::Medium,
            $sigma < 4.0 => self::High,
            default => self::Critical,
        };
    }

    /**
     * Map to AlertSeverity for monitoring integration
     */
    public function toAlertSeverity(): AlertSeverity
    {
        return match ($this) {
            self::None => AlertSeverity::Ok,
            self::Low => AlertSeverity::Info,
            self::Medium => AlertSeverity::Warning,
            self::High, self::Critical => AlertSeverity::Critical,
        };
    }

    /**
     * Expected response time in minutes
     */
    public function expectedResponseMinutes(): int
    {
        return match ($this) {
            self::Critical => 5,
            self::High => 15,
            self::Medium => 60,
            self::Low => 240,
            default => 0,
        };
    }
}

// ============================================================================
// Compliance Status
// ============================================================================

enum ComplianceStatus: string
{
    case Compliant = 'compliant';
    case Warning = 'warning';
    case Violation = 'violation';
    case Unknown = 'unknown';

    public function isCompliant(): bool
    {
        return $this === self::Compliant;
    }

    public function isViolation(): bool
    {
        return $this === self::Violation;
    }

    /**
     * From retention compliance check
     */
    public static function fromRetentionCheck(int $actualDays, int $requiredDays): self
    {
        if ($actualDays < 0) {
            return self::Unknown;
        }

        return match (true) {
            $actualDays >= $requiredDays => self::Compliant,
            $actualDays >= $requiredDays * 0.8 => self::Warning,
            default => self::Violation,
        };
    }

    /**
     * From encryption coverage percentage
     */
    public static function fromEncryptionCoverage(float $percent): self
    {
        return match (true) {
            $percent >= 1.0 => self::Compliant,
            $percent >= 0.9 => self::Warning,
            default => self::Violation,
        };
    }
}

// ============================================================================
// Encryption Algorithm
// ============================================================================

enum EncryptionAlgorithm: string
{
    case AES256GCM = 'aes-256-gcm';
    case AES256CBC = 'aes-256-cbc';
    case AES128CBC = 'aes-128-cbc';
    case ChaCha20Poly1305 = 'chacha20-poly1305';
    case None = 'none';

    public function isAuthenticated(): bool
    {
        return in_array($this, [self::AES256GCM, self::ChaCha20Poly1305], true);
    }

    public function keyLengthBytes(): int
    {
        return match ($this) {
            self::AES256GCM, self::AES256CBC, self::ChaCha20Poly1305 => 32,
            self::AES128CBC => 16,
            default => 0,
        };
    }

    /**
     * Recommended algorithm for a data classification
     */
    public static function forClassification(DataClassification $classification): self
    {
        return match ($classification) {
            DataClassification::Public => self::None,
            DataClassification::Internal => self::AES128CBC,
            DataClassification::Confidential, DataClassification::Restricted => self::AES256GCM,
            DataClassification::TopSecret => self::AES256GCM,
        };
    }
}

// ============================================================================
// Anonymization Method
// ============================================================================

enum AnonymizationMethod: string
{
    case Hashing = 'hashing';
    case Tokenization = 'tokenization';
    case Pseudonymization = 'pseudonymization';
    case Generalization = 'generalization';
    case DifferentialPrivacy = 'differential_privacy';
    case KAnonymity = 'k_anonymity';
    case Suppression = 'suppression';

    /**
     * Is this method reversible?
     */
    public function isReversible(): bool
    {
        return in_array($this, [self::Tokenization, self::Pseudonymization], true);
    }

    /**
     * Does this method preserve data utility for ML?
     */
    public function preservesMLUtility(): bool
    {
        return in_array($this, [
            self::DifferentialPrivacy,
            self::KAnonymity,
            self::Generalization,
            self::Pseudonymization,
        ], true);
    }

    /**
     * Recommended method for ML feature anonymization
     */
    public static function forMLFeatures(): array
    {
        return [self::DifferentialPrivacy, self::KAnonymity, self::Pseudonymization];
    }
}

// ============================================================================
// Access Decision (Zero Trust)
// ============================================================================

enum AccessDecision: string
{
    case Allow = 'allow';
    case Deny = 'deny';
    case DenyWithAudit = 'deny_with_audit';
    case RequireMFA = 'require_mfa';
    case RequireJIT = 'require_jit';
    case RateLimited = 'rate_limited';

    public function isAllowed(): bool
    {
        return $this === self::Allow;
    }

    public function isDenied(): bool
    {
        return in_array($this, [self::Deny, self::DenyWithAudit], true);
    }

    public function requiresAdditionalAuth(): bool
    {
        return in_array($this, [self::RequireMFA, self::RequireJIT], true);
    }
}

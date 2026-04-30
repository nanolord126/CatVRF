<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use Carbon\CarbonImmutable;

use App\Domains\RealEstate\Models\PropertyViewing;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Str;
use Carbon\Carbon;

final readonly class RealEstateFaceIDVerificationService
{
    private const TOKEN_TTL_SECONDS = 300;

    private const VERIFICATION_CACHE_TTL = 600;

    private const MAX_VERIFICATION_ATTEMPTS = 3;

    private const ATTEMPT_WINDOW_MINUTES = 15;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly RedisConnection $redis,
    ) {}

    public function generateVerificationToken(
        int $userId,
        int $propertyId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'generate_faceid_token',
            0,
            null,
            null,
            $correlationId
        );

        $attemptsKey = $this->getAttemptsKey($userId, $propertyId);
        $currentAttempts = (int) $this->redis->get($attemptsKey) ?? 0;

        if ($currentAttempts >= self::MAX_VERIFICATION_ATTEMPTS) {
            throw new \DomainException('Maximum verification attempts exceeded. Please try again later.');
        }

        $token = Str::random(64);
        $tokenData = [
            'user_id' => $userId,
            'property_id' => $propertyId,
            'correlation_id' => $correlationId,
            'expires_at' => CarbonImmutable::now()->addSeconds(self::TOKEN_TTL_SECONDS)->toIso8601String(),
            'created_at' => CarbonImmutable::now()->toIso8601String(),
        ];

        $this->redis->setex(
            $this->getTokenKey($token),
            self::TOKEN_TTL_SECONDS,
            json_encode($tokenData)
        );

        $this->redis->incr($attemptsKey);
        $this->redis->expire($attemptsKey, self::ATTEMPT_WINDOW_MINUTES * 60);

        $this->audit->record(
            'faceid_token_generated',
            'App\Domains\RealEstate\Models\Property',
            $propertyId,
            [],
            [
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => $tokenData['expires_at'],
            ],
            $correlationId
        );

        return [
            'token' => $token,
            'expires_at' => $tokenData['expires_at'],
            'ttl_seconds' => self::TOKEN_TTL_SECONDS,
            'attempts_remaining' => self::MAX_VERIFICATION_ATTEMPTS - ($currentAttempts + 1),
        ];
    }

    public function verifyFaceID(
        string $token,
        string $verificationResult,
        int $expectedUserId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $expectedUserId,
            'verify_faceid',
            0,
            null,
            null,
            $correlationId
        );

        $tokenDataJson = $this->redis->get($this->getTokenKey($token));

        if ($tokenDataJson === null) {
            throw new \DomainException('Invalid or expired verification token');
        }

        $tokenData = json_decode($tokenDataJson, true);

        if ($tokenData['user_id'] !== $expectedUserId) {
            $this->redis->del($this->getTokenKey($token));
            throw new \DomainException('Token user mismatch');
        }

        $verification = $this->parseVerificationResult($verificationResult);

        if ($verification['verified'] !== true) {
            $this->audit->record(
                'faceid_verification_failed',
                'App\Domains\RealEstate\Models\Property',
                $tokenData['property_id'],
                [],
                [
                    'user_id' => $expectedUserId,
                    'reason' => $verification['reason'] ?? 'Verification failed',
                ],
                $correlationId
            );

            throw new \DomainException('FaceID verification failed: '.($verification['reason'] ?? 'Unknown error'));
        }

        if ($verification['confidence_score'] < 0.85) {
            $this->audit->record(
                'faceid_low_confidence',
                'App\Domains\RealEstate\Models\Property',
                $tokenData['property_id'],
                [],
                [
                    'user_id' => $expectedUserId,
                    'confidence_score' => $verification['confidence_score'],
                ],
                $correlationId
            );

            throw new \DomainException('FaceID verification confidence too low');
        }

        $this->redis->del($this->getTokenKey($token));
        $this->redis->del($this->getAttemptsKey($expectedUserId, $tokenData['property_id']));

        $verificationRecord = [
            'user_id' => $expectedUserId,
            'property_id' => $tokenData['property_id'],
            'verified' => true,
            'confidence_score' => $verification['confidence_score'],
            'verification_method' => $verification['method'] ?? 'biometric',
            'verified_at' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        $cacheKey = $this->getVerificationCacheKey($expectedUserId, $tokenData['property_id']);
        $this->redis->setex($cacheKey, self::VERIFICATION_CACHE_TTL, json_encode($verificationRecord));

        $this->audit->record(
            'faceid_verified',
            'App\Domains\RealEstate\Models\Property',
            $tokenData['property_id'],
            [],
            [
                'user_id' => $expectedUserId,
                'confidence_score' => $verification['confidence_score'],
                'verification_method' => $verification['verification_method'],
            ],
            $correlationId
        );

        return [
            'verified' => true,
            'confidence_score' => $verification['confidence_score'],
            'verification_method' => $verification['method'],
            'verified_at' => $verificationRecord['verified_at'],
            'property_id' => $tokenData['property_id'],
        ];
    }

    public function validateViewingWithFaceID(
        PropertyViewing $viewing,
        string $correlationId
    ): void {
        $cacheKey = $this->getVerificationCacheKey($viewing->user_id, $viewing->property_id);
        $verificationJson = $this->redis->get($cacheKey);

        if ($verificationJson === null) {
            throw new \DomainException('No valid FaceID verification found for this viewing');
        }

        $verification = json_decode($verificationJson, true);

        if ($verification['verified'] !== true) {
            throw new \DomainException('FaceID verification not valid');
        }

        $verificationAge = CarbonImmutable::now()->diffInSeconds(Carbon::parse($verification['verified_at']));

        if ($verificationAge > self::VERIFICATION_CACHE_TTL) {
            $this->redis->del($cacheKey);
            throw new \DomainException('FaceID verification has expired');
        }

        $viewing->update([
            'face_id_verified' => true,
            'face_id_verification_result' => json_encode($verification),
            'fraud_score' => $this->calculateFraudScore($verification),
        ]);

        $this->audit->record(
            'viewing_faceid_validated',
            'App\Domains\RealEstate\Models\PropertyViewing',
            $viewing->id,
            [],
            [
                'property_id' => $viewing->property_id,
                'user_id' => $viewing->user_id,
                'confidence_score' => $verification['confidence_score'],
            ],
            $correlationId
        );
    }

    public function checkVerificationStatus(
        int $userId,
        int $propertyId,
        string $correlationId
    ): array {
        $cacheKey = $this->getVerificationCacheKey($userId, $propertyId);
        $verificationJson = $this->redis->get($cacheKey);

        if ($verificationJson === null) {
            return [
                'verified' => false,
                'reason' => 'No verification found',
            ];
        }

        $verification = json_decode($verificationJson, true);
        $verifiedAt = Carbon::parse($verification['verified_at']);
        $expiresAt = $verifiedAt->addSeconds(self::VERIFICATION_CACHE_TTL);

        return [
            'verified' => $verification['verified'] && $expiresAt->isFuture(),
            'confidence_score' => $verification['confidence_score'] ?? null,
            'verification_method' => $verification['verification_method'] ?? null,
            'verified_at' => $verification['verified_at'],
            'expires_at' => $expiresAt->toIso8601String(),
            'seconds_until_expiry' => max(0, $expiresAt->diffInSeconds(CarbonImmutable::now())),
        ];
    }

    public function revokeVerification(
        int $userId,
        int $propertyId,
        string $correlationId
    ): void {
        $cacheKey = $this->getVerificationCacheKey($userId, $propertyId);
        $verificationJson = $this->redis->get($cacheKey);

        if ($verificationJson !== null) {
            $verification = json_decode($verificationJson, true);

            $this->audit->record(
                'faceid_verification_revoked',
                'App\Domains\RealEstate\Models\Property',
                $propertyId,
                $verification,
                [],
                $correlationId
            );

            $this->redis->del($cacheKey);
        }

        $this->redis->del($this->getAttemptsKey($userId, $propertyId));
    }

    private function parseVerificationResult(string $verificationResult): array
    {
        $decoded = json_decode($verificationResult, true);

        if ($decoded === null) {
            return [
                'verified' => false,
                'reason' => 'Invalid verification result format',
                'confidence_score' => 0.0,
            ];
        }

        return [
            'verified' => $decoded['verified'] ?? false,
            'confidence_score' => $decoded['confidence_score'] ?? 0.0,
            'reason' => $decoded['reason'] ?? null,
            'method' => $decoded['method'] ?? 'biometric',
        ];
    }

    private function calculateFraudScore(array $verification): float
    {
        $baseScore = 0.0;

        if (($verification['confidence_score'] ?? 1.0) < 0.90) {
            $baseScore += 0.2;
        }

        if (($verification['confidence_score'] ?? 1.0) < 0.85) {
            $baseScore += 0.3;
        }

        return min(1.0, $baseScore);
    }

    private function getTokenKey(string $token): string
    {
        return "re:faceid:token:{$token}";
    }

    private function getAttemptsKey(int $userId, int $propertyId): string
    {
        return "re:faceid:attempts:{$userId}:{$propertyId}";
    }

    private function getVerificationCacheKey(int $userId, int $propertyId): string
    {
        return "re:faceid:verified:{$userId}:{$propertyId}";
    }
}

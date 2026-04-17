<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\RealEstateFaceIDVerificationService;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyViewing;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

final class RealEstateFaceIDVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealEstateFaceIDVerificationService $service;
    private Tenant $tenant;
    private Property $property;
    private User $user;
    private PropertyViewing $viewing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RealEstateFaceIDVerificationService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
        $this->viewing = PropertyViewing::factory()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => User::factory()->create()->id,
        ]);
    }

    public function test_generate_verification_token_returns_valid_token(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->generateVerificationToken(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertArrayHasKey('ttl_seconds', $result);
        $this->assertArrayHasKey('attempts_remaining', $result);
        $this->assertIsString($result['token']);
        $this->assertEquals(300, $result['ttl_seconds']);
        $this->assertGreaterThanOrEqual(0, $result['attempts_remaining']);
    }

    public function test_generate_verification_token_limits_attempts(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        for ($i = 0; $i < 4; $i++) {
            $result = $this->service->generateVerificationToken(
                $this->user->id,
                $this->property->id,
                $correlationId
            );
        }

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Maximum verification attempts exceeded');

        $this->service->generateVerificationToken(
            $this->user->id,
            $this->property->id,
            $correlationId
        );
    }

    public function test_verify_face_id_with_valid_token(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $tokenResult = $this->service->generateVerificationToken(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $verificationResult = json_encode([
            'verified' => true,
            'confidence_score' => 0.95,
            'method' => 'biometric',
        ]);

        $result = $this->service->verifyFaceID(
            $tokenResult['token'],
            $verificationResult,
            $this->user->id,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['verified']);
        $this->assertEquals(0.95, $result['confidence_score']);
        $this->assertEquals('biometric', $result['verification_method']);
    }

    public function test_verify_face_id_rejects_low_confidence(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $tokenResult = $this->service->generateVerificationToken(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $verificationResult = json_encode([
            'verified' => true,
            'confidence_score' => 0.70,
            'method' => 'biometric',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('FaceID verification confidence too low');

        $this->service->verifyFaceID(
            $tokenResult['token'],
            $verificationResult,
            $this->user->id,
            $correlationId
        );
    }

    public function test_verify_face_id_rejects_invalid_token(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $verificationResult = json_encode([
            'verified' => true,
            'confidence_score' => 0.95,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid or expired verification token');

        $this->service->verifyFaceID(
            'invalid_token',
            $verificationResult,
            $this->user->id,
            $correlationId
        );
    }

    public function test_verify_face_id_rejects_user_mismatch(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $tokenResult = $this->service->generateVerificationToken(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $verificationResult = json_encode([
            'verified' => true,
            'confidence_score' => 0.95,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token user mismatch');

        $this->service->verifyFaceID(
            $tokenResult['token'],
            $verificationResult,
            99999,
            $correlationId
        );
    }

    public function test_check_verification_status_with_no_verification(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->checkVerificationStatus(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertFalse($result['verified']);
        $this->assertEquals('No verification found', $result['reason']);
    }

    public function test_check_verification_status_with_valid_verification(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $tokenResult = $this->service->generateVerificationToken(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $verificationResult = json_encode([
            'verified' => true,
            'confidence_score' => 0.95,
            'method' => 'biometric',
        ]);

        $this->service->verifyFaceID(
            $tokenResult['token'],
            $verificationResult,
            $this->user->id,
            $correlationId
        );

        $status = $this->service->checkVerificationStatus(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $this->assertTrue($status['verified']);
        $this->assertEquals(0.95, $status['confidence_score']);
    }

    public function test_revoke_verification_removes_cached_data(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $tokenResult = $this->service->generateVerificationToken(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $verificationResult = json_encode([
            'verified' => true,
            'confidence_score' => 0.95,
        ]);

        $this->service->verifyFaceID(
            $tokenResult['token'],
            $verificationResult,
            $this->user->id,
            $correlationId
        );

        $this->service->revokeVerification(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $status = $this->service->checkVerificationStatus(
            $this->user->id,
            $this->property->id,
            $correlationId
        );

        $this->assertFalse($status['verified']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Redis::flushdb();
        parent::tearDown();
    }
}

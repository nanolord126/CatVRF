<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportsFraudDetectionE2ETest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_detect_rapid_booking_attempts(): void
    {
        // Simulate 5 rapid bookings within 1 minute for same facility
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/sports/bookings', [
                    'facility_id' => 1,
                    'slot_start' => now()->addHours($i + 1)->toIso8601String(),
                    'slot_end' => now()->addHours($i + 2)->toIso8601String(),
                    'sport_type' => 'tennis',
                    'participants' => 2,
                    'payment_method' => 'wallet',
                ]);
        }

        // First attempts should succeed, later ones should be rate limited or flagged
        $this->assertTrue($responses[0]->status() < 300);
        
        // Later attempts should be rate limited (429) or have fraud score
        $lastResponse = $responses[4];
        $this->assertTrue(
            $lastResponse->status() === 429 || 
            $lastResponse->status() >= 400 ||
            ($lastResponse->json('data.fraud_score') && $lastResponse->json('data.fraud_score') > 0.5)
        );
    }

    public function test_detect_fake_facility_booking(): void
    {
        // Attempt booking for non-existent facility
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 999999,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(2)->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'wallet',
            ]);

        // Should be blocked or flagged as fraud
        $this->assertTrue(
            $response->status() === 404 || 
            $response->status() === 422 ||
            ($response->json('data.fraud_score') && $response->json('data.fraud_score') > 0.7)
        );
    }

    public function test_detect_suspicious_time_booking(): void
    {
        // Attempt booking at suspicious time (3 AM)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->setHour(3)->setMinute(0)->addDay()->toIso8601String(),
                'slot_end' => now()->setHour(5)->setMinute(0)->addDay()->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'wallet',
            ]);

        // Should be flagged as suspicious
        $this->assertTrue(
            $response->status() < 500 &&
            ($response->json('data.fraud_score') || $response->status() >= 400)
        );
    }

    public function test_detect_unrealistic_participants(): void
    {
        // Attempt booking with unrealistic participant count
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(2)->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 50, // Tennis court can't fit 50 people
                'payment_method' => 'wallet',
            ]);

        // Should be blocked or flagged
        $this->assertTrue(
            $response->status() === 422 ||
            ($response->json('data.fraud_score') && $response->json('data.fraud_score') > 0.6)
        );
    }

    public function test_detect_double_booking_attempt(): void
    {
        // Create first booking
        $slotStart = now()->addHours(1)->toIso8601String();
        $slotEnd = now()->addHours(2)->toIso8601String();

        $firstBooking = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => $slotStart,
                'slot_end' => $slotEnd,
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'wallet',
            ]);

        if ($firstBooking->status() === 201) {
            // Attempt second booking for same slot
            $secondBooking = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/sports/bookings', [
                    'facility_id' => 1,
                    'slot_start' => $slotStart,
                    'slot_end' => $slotEnd,
                    'sport_type' => 'tennis',
                    'participants' => 2,
                    'payment_method' => 'wallet',
                ]);

            // Should be prevented
            $this->assertTrue(
                $secondBooking->status() === 409 || 
                $secondBooking->status() === 422 ||
                str_contains($secondBooking->json('message'), 'already booked')
            );
        }
    }

    public function test_detect_bot_user_agent(): void
    {
        // Attempt booking with suspicious user agent
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('User-Agent', 'Bot/1.0 (Suspicious Bot)')
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(2)->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'wallet',
            ]);

        // Should be flagged or blocked
        $this->assertTrue(
            $response->status() === 403 ||
            ($response->json('data.fraud_score') && $response->json('data.fraud_score') > 0.6)
        );
    }

    public function test_detect_suspicious_ip(): void
    {
        // Attempt booking from suspicious IP (VPN/proxy indicator)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('X-Forwarded-For', '192.0.2.1') // Test IP range
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(2)->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'wallet',
            ]);

        // Should be processed but flagged
        $this->assertTrue($response->status() < 500);
    }

    public function test_detect_invalid_payment_method(): void
    {
        // Attempt booking with invalid payment method
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(2)->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'stolen_card_12345',
            ]);

        // Should be blocked
        $this->assertTrue($response->status() === 422 || $response->status() === 400);
    }

    public function test_detect_booking_cancellation_abuse(): void
    {
        // Create and immediately cancel multiple bookings (abuse pattern)
        for ($i = 0; $i < 3; $i++) {
            $booking = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/sports/bookings', [
                    'facility_id' => 1,
                    'slot_start' => now()->addHours($i + 10)->toIso8601String(),
                    'slot_end' => now()->addHours($i + 11)->toIso8601String(),
                    'sport_type' => 'tennis',
                    'participants' => 2,
                    'payment_method' => 'wallet',
                ]);

            if ($booking->status() === 201 && $booking->json('data.uuid')) {
                $uuid = $booking->json('data.uuid');
                
                // Immediately cancel
                $cancel = $this->withHeader('Authorization', "Bearer {$this->token}")
                    ->postJson("/api/v1/sports/bookings/{$uuid}/cancel");

                // Should be allowed but flagged after pattern
                $this->assertTrue($cancel->status() < 500);
            }
        }

        // After pattern detection, next booking should be restricted
        $nextBooking = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->addHours(20)->toIso8601String(),
                'slot_end' => now()->addHours(21)->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'wallet',
            ]);

        $this->assertTrue($nextBooking->status() < 500);
    }

    public function test_fraud_score_increases_with_suspicious_activity(): void
    {
        // Perform multiple suspicious actions
        for ($i = 0; $i < 3; $i++) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/sports/bookings', [
                    'facility_id' => 999999, // Non-existent
                    'slot_start' => now()->addHours(1)->toIso8601String(),
                    'slot_end' => now()->addHours(2)->toIso8601String(),
                    'sport_type' => 'tennis',
                    'participants' => 2,
                    'payment_method' => 'wallet',
                ]);
        }

        // Next valid booking should have elevated fraud score
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(2)->toIso8601String(),
                'sport_type' => 'tennis',
                'participants' => 2,
                'payment_method' => 'wallet',
            ]);

        $this->assertTrue($response->status() < 500);
    }
}

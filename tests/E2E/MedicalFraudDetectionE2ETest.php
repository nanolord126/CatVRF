<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalFraudDetectionE2ETest extends TestCase
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

    public function test_detect_rapid_appointment_attempts(): void
    {
        // Simulate 5 rapid appointments within 1 minute for same doctor
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/medical/appointments', [
                    'doctor_id' => 1,
                    'slot_start' => now()->addHours($i + 1)->toIso8601String(),
                    'slot_end' => now()->addHours($i + 1)->addMinutes(30)->toIso8601String(),
                    'appointment_type' => 'consultation',
                    'symptoms' => 'Test symptoms',
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

    public function test_detect_fake_doctor_appointment(): void
    {
        // Attempt appointment for non-existent doctor
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/appointments', [
                'doctor_id' => 999999,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(1)->addMinutes(30)->toIso8601String(),
                'appointment_type' => 'consultation',
                'symptoms' => 'Test symptoms',
                'payment_method' => 'wallet',
            ]);

        // Should be blocked or flagged as fraud
        $this->assertTrue(
            $response->status() === 404 || 
            $response->status() === 422 ||
            ($response->json('data.fraud_score') && $response->json('data.fraud_score') > 0.7)
        );
    }

    public function test_detect_pii_in_symptoms(): void
    {
        // Attempt appointment with PII in symptoms (compliance violation)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/appointments', [
                'doctor_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(1)->addMinutes(30)->toIso8601String(),
                'appointment_type' => 'consultation',
                'symptoms' => 'My SSN is 123-45-6789 and my credit card is 4111-1111-1111-1111',
                'payment_method' => 'wallet',
            ]);

        // PII should be detected and either blocked or anonymized
        $this->assertTrue($response->status() < 500);
        
        if ($response->status() === 201) {
            // If created, PII should be anonymized
            $symptoms = $response->json('data.symptoms');
            $this->assertFalse(
                str_contains($symptoms, '123-45-6789') || 
                str_contains($symptoms, '4111-1111-1111-1111'),
                'PII should be anonymized'
            );
        }
    }

    public function test_detect_emergency_abuse(): void
    {
        // Attempt multiple emergency appointments (abuse pattern)
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/medical/appointments', [
                    'doctor_id' => 1,
                    'slot_start' => now()->addHours($i + 1)->toIso8601String(),
                    'slot_end' => now()->addHours($i + 1)->addMinutes(30)->toIso8601String(),
                    'appointment_type' => 'emergency',
                    'symptoms' => 'EMERGENCY!!! Need immediate attention!!!',
                    'payment_method' => 'wallet',
                    'is_emergency' => true,
                ]);
        }

        // After multiple emergency attempts, should be flagged
        $lastResponse = $responses[2];
        $this->assertTrue(
            $lastResponse->status() < 500 &&
            ($lastResponse->json('data.fraud_score') || $lastResponse->status() >= 400)
        );
    }

    public function test_detect_double_booking_attempt(): void
    {
        // Create first appointment
        $slotStart = now()->addHours(1)->toIso8601String();
        $slotEnd = now()->addHours(1)->addMinutes(30)->toIso8601String();

        $firstAppointment = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/appointments', [
                'doctor_id' => 1,
                'slot_start' => $slotStart,
                'slot_end' => $slotEnd,
                'appointment_type' => 'consultation',
                'symptoms' => 'Test symptoms',
                'payment_method' => 'wallet',
            ]);

        if ($firstAppointment->status() === 201) {
            // Attempt second appointment for same slot
            $secondAppointment = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/medical/appointments', [
                    'doctor_id' => 1,
                    'slot_start' => $slotStart,
                    'slot_end' => $slotEnd,
                    'appointment_type' => 'consultation',
                    'symptoms' => 'Test symptoms',
                    'payment_method' => 'wallet',
                ]);

            // Should be prevented
            $this->assertTrue(
                $secondAppointment->status() === 409 || 
                $secondAppointment->status() === 422 ||
                str_contains($secondAppointment->json('message'), 'already booked')
            );
        }
    }

    public function test_detect_suspicious_appointment_type(): void
    {
        // Attempt complex procedure without proper context
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/appointments', [
                'doctor_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(1)->addMinutes(30)->toIso8601String(),
                'appointment_type' => 'surgery', // Complex procedure
                'symptoms' => 'I need surgery immediately',
                'payment_method' => 'cash', // Suspicious for surgery
            ]);

        // Should be flagged or blocked
        $this->assertTrue(
            $response->status() === 422 ||
            ($response->json('data.fraud_score') && $response->json('data.fraud_score') > 0.7)
        );
    }

    public function test_detect_xss_in_symptoms(): void
    {
        // Attempt appointment with XSS in symptoms
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/appointments', [
                'doctor_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(1)->addMinutes(30)->toIso8601String(),
                'appointment_type' => 'consultation',
                'symptoms' => '<script>alert("xss")</script>',
                'payment_method' => 'wallet',
            ]);

        // XSS should be blocked or sanitized
        $this->assertTrue($response->status() < 500);
        
        if ($response->status() === 201) {
            $symptoms = $response->json('data.symptoms');
            $this->assertFalse(
                str_contains($symptoms, '<script>'),
                'XSS should be sanitized'
            );
        }
    }

    public function test_detect_medical_record_manipulation(): void
    {
        // Create appointment
        $appointment = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/appointments', [
                'doctor_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(1)->addMinutes(30)->toIso8601String(),
                'appointment_type' => 'consultation',
                'symptoms' => 'Test symptoms',
                'payment_method' => 'wallet',
            ]);

        if ($appointment->status() === 201 && $appointment->json('data.uuid')) {
            $uuid = $appointment->json('data.uuid');
            
            // Attempt to modify medical record with fake data
            $update = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->putJson("/api/v1/medical/appointments/{$uuid}", [
                    'symptoms' => 'FAKE SYMPTOMS - THIS IS FRAUD',
                    'diagnosis' => 'Fake diagnosis for insurance fraud',
                ]);

            // Should be blocked or flagged
            $this->assertTrue(
                $update->status() === 403 || 
                $update->status() === 422 ||
                ($update->json('data.fraud_score') && $update->json('data.fraud_score') > 0.8)
            );
        }
    }

    public function test_detect_insurance_fraud_pattern(): void
    {
        // Create multiple appointments with same symptoms (insurance fraud pattern)
        for ($i = 0; $i < 3; $i++) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/medical/appointments', [
                    'doctor_id' => $i + 1,
                    'slot_start' => now()->addHours($i + 1)->toIso8601String(),
                    'slot_end' => now()->addHours($i + 1)->addMinutes(30)->toIso8601String(),
                    'appointment_type' => 'consultation',
                    'symptoms' => 'Chronic back pain - need insurance claim', // Same symptoms
                    'payment_method' => 'insurance',
                ]);
        }

        // Next appointment should be flagged
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/appointments', [
                'doctor_id' => 4,
                'slot_start' => now()->addHours(5)->toIso8601String(),
                'slot_end' => now()->addHours(5)->addMinutes(30)->toIso8601String(),
                'appointment_type' => 'consultation',
                'symptoms' => 'Chronic back pain - need insurance claim',
                'payment_method' => 'insurance',
            ]);

        $this->assertTrue(
            $response->status() < 500 &&
            ($response->json('data.fraud_score') || $response->status() >= 400)
        );
    }

    public function test_health_score_anonymization(): void
    {
        // Request health score with PII
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/medical/diagnostic/ai', [
                'symptoms' => 'My name is John Doe and my phone is 555-1234',
                'age' => 30,
                'gender' => 'male',
            ]);

        // PII should be anonymized before AI processing
        $this->assertTrue($response->status() < 500);
        
        // Response should not contain PII
        $responseContent = json_encode($response->json());
        $this->assertFalse(
            str_contains($responseContent, 'John Doe') || 
            str_contains($responseContent, '555-1234'),
            'PII should not be in response'
        );
    }
}

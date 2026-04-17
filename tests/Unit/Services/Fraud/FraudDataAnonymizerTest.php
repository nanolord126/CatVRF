<?php declare(strict_types=1);

namespace Tests\Unit\Services\Fraud;

use App\Services\Fraud\FraudDataAnonymizer;
use Tests\TestCase;

final class FraudDataAnonymizerTest extends TestCase
{
    private FraudDataAnonymizer $anonymizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->anonymizer = app(FraudDataAnonymizer::class);
    }

    public function test_anonymizes_medical_symptoms(): void
    {
        $context = [
            'symptoms' => 'headache, fever, cough',
            'amount' => 1000,
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'ai_diagnosis');

        $this->assertEquals('***', $result['symptoms']);
        $this->assertEquals(1000, $result['amount']);
    }

    public function test_anonymizes_diagnosis(): void
    {
        $context = [
            'diagnosis' => 'acute bronchitis',
            'user_id' => 123,
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'predict_health_score');

        $this->assertEquals('***', $result['diagnosis']);
        $this->assertEquals(123, $result['user_id']);
    }

    public function test_anonymizes_lab_results(): void
    {
        $context = [
            'lab_results' => ['blood_pressure' => '120/80', 'temperature' => '37.5'],
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'lab_result_upload');

        $this->assertEquals(['***', '***'], $result['lab_results']);
    }

    public function test_anonymizes_medical_history(): void
    {
        $context = [
            'medical_history' => 'diabetes type 2, hypertension',
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'medical_record_create');

        $this->assertEquals('***', $result['medical_history']);
    }

    public function test_anonymizes_prescriptions(): void
    {
        $context = [
            'prescriptions' => ['amoxicillin 500mg', 'ibuprofen 400mg'],
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'medical_record_update');

        $this->assertEquals(['***', '***'], $result['prescriptions']);
    }

    public function test_anonymizes_email(): void
    {
        $context = [
            'email' => 'user@example.com',
            'amount' => 1000,
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'payment_init');

        $this->assertStringContainsString('@example.com', $result['email']);
        $this->assertStringContainsString('*', $result['email']);
        $this->assertNotEquals('user@example.com', $result['email']);
    }

    public function test_anonymizes_phone(): void
    {
        $context = [
            'phone' => '+79001234567',
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'payment_init');

        $this->assertStringEndsWith('4567', $result['phone']);
        $this->assertStringContainsString('*', $result['phone']);
        $this->assertNotEquals('+79001234567', $result['phone']);
    }

    public function test_anonymizes_credit_card(): void
    {
        $context = [
            'credit_card' => '4111111111111111',
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'payment_init');

        $this->assertStringEndsWith('1111', $result['credit_card']);
        $this->assertStringContainsString('*', $result['credit_card']);
        $this->assertNotEquals('4111111111111111', $result['credit_card']);
    }

    public function test_anonymizes_full_name(): void
    {
        $context = [
            'full_name' => 'Ivan Ivanovich Ivanov',
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'payment_init');

        $this->assertStringContainsString('*', $result['full_name']);
        $this->assertNotEquals('Ivan Ivanovich Ivanov', $result['full_name']);
    }

    public function test_does_not_anonymize_non_medical_operations(): void
    {
        $context = [
            'symptoms' => 'headache',
            'amount' => 1000,
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'payment_init');

        $this->assertEquals('headache', $result['symptoms']);
    }

    public function test_anonymizes_nested_medical_data(): void
    {
        $context = [
            'context' => [
                'symptoms' => 'fever',
                'diagnosis' => 'flu',
            ],
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'ai_diagnosis');

        $this->assertEquals('***', $result['context']['symptoms']);
        $this->assertEquals('***', $result['context']['diagnosis']);
    }

    public function test_validate_anonymization_detects_pii_leak(): void
    {
        $original = ['symptoms' => 'headache'];
        $anonymized = ['symptoms' => 'headache']; // Not anonymized!

        $isValid = $this->anonymizer->validateAnonymization($original, $anonymized);

        $this->assertFalse($isValid);
    }

    public function test_validate_anonymization_passes_for_properly_anonymized(): void
    {
        $original = ['symptoms' => 'headache'];
        $anonymized = ['symptoms' => '***'];

        $isValid = $this->anonymizer->validateAnonymization($original, $anonymized);

        $this->assertTrue($isValid);
    }

    public function test_anonymizes_all_medical_fields(): void
    {
        $context = [
            'symptoms' => 'fever',
            'diagnosis' => 'flu',
            'lab_results' => ['high temp'],
            'medical_history' => 'diabetes',
            'prescriptions' => ['antibiotics'],
            'allergies' => 'penicillin',
            'chronic_conditions' => 'asthma',
            'vital_signs' => ['bp: 120/80'],
            'patient_notes' => 'smoker',
            'doctor_notes' => 'monitor',
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'ai_diagnosis');

        foreach (array_keys($context) as $field) {
            $this->assertArrayHasKey($field, $result);
            if (is_array($context[$field])) {
                $this->assertContains('***', $result[$field]);
            } else {
                $this->assertEquals('***', $result[$field]);
            }
        }
    }

    public function test_preserves_non_sensitive_data(): void
    {
        $context = [
            'amount' => 1000,
            'operation_type' => 'payment',
            'user_id' => 123,
            'timestamp' => '2024-01-01',
        ];

        $result = $this->anonymizer->anonymizeContext($context, 'payment_init');

        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('payment', $result['operation_type']);
        $this->assertEquals(123, $result['user_id']);
        $this->assertEquals('2024-01-01', $result['timestamp']);
    }
}

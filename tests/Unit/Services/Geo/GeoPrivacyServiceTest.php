<?php declare(strict_types=1);

namespace Tests\Unit\Services\Geo;

use App\Services\Geo\GeoPrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GeoPrivacyServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeoPrivacyService $privacyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->privacyService = app(GeoPrivacyService::class);
    }

    public function test_anonymize_medical_coordinates_reduces_precision(): void
    {
        $original = ['lat' => 55.755833, 'lon' => 37.617777];
        $anonymized = $this->privacyService->anonymizeMedicalCoordinates($original['lat'], $original['lon']);
        
        $this->assertIsArray($anonymized);
        $this->assertArrayHasKey('lat', $anonymized);
        $this->assertArrayHasKey('lon', $anonymized);
        $this->assertArrayHasKey('precision', $anonymized);
        $this->assertArrayHasKey('anonymized_at', $anonymized);
        $this->assertLessThanOrEqual(3, $anonymized['precision']);
    }

    public function test_anonymize_coordinates_with_medical_context(): void
    {
        $anonymized = $this->privacyService->anonymizeCoordinates(55.755833, 37.617777, 'patient_address');
        
        $this->assertArrayHasKey('lat', $anonymized);
        $this->assertArrayHasKey('precision', $anonymized);
        // Medical context should have lower precision
        $this->assertLessThanOrEqual(3, $anonymized['precision']);
    }

    public function test_anonymize_coordinates_without_context(): void
    {
        $anonymized = $this->privacyService->anonymizeCoordinates(55.755833, 37.617777);
        
        $this->assertArrayHasKey('lat', $anonymized);
        $this->assertArrayHasKey('precision', $anonymized);
        // Default precision
        $this->assertLessThanOrEqual(4, $anonymized['precision']);
    }

    public function test_get_privacy_geohash_for_medical_context(): void
    {
        $geohash = $this->privacyService->getPrivacyGeohash(55.75, 37.62, 'medical');
        
        $this->assertIsString($geohash);
        // Medical context should have lower precision (shorter geohash)
        $this->assertLessThanOrEqual(5, strlen($geohash));
    }

    public function test_get_privacy_geohash_for_general_context(): void
    {
        $geohash = $this->privacyService->getPrivacyGeohash(55.75, 37.62, 'delivery');
        
        $this->assertIsString($geohash);
        $this->assertGreaterThan(0, strlen($geohash));
    }

    public function test_validate_anonymization_returns_true_for_correct_precision(): void
    {
        $coords = ['lat' => 55.756, 'lon' => 37.618];
        
        $isValid = $this->privacyService->validateAnonymization($coords, 4);
        
        $this->assertTrue($isValid);
    }

    public function test_validate_anonymization_returns_false_for_incorrect_precision(): void
    {
        $coords = ['lat' => 55.755833, 'lon' => 37.617777];
        
        $isValid = $this->privacyService->validateAnonymization($coords, 4);
        
        $this->assertFalse($isValid);
    }

    public function test_get_privacy_zone_radius(): void
    {
        $radius = $this->privacyService->getPrivacyZoneRadius(5);
        
        $this->assertIsFloat($radius);
        $this->assertGreaterThan(0, $radius);
    }

    public function test_log_privacy_event(): void
    {
        $this->expectNotToPerformAssertions();
        
        $this->privacyService->logPrivacyEvent(
            'anonymization',
            1,
            ['lat' => 55.755833, 'lon' => 37.617777],
            ['lat' => 55.756, 'lon' => 37.618],
            'medical'
        );
    }

    public function test_record_consent(): void
    {
        $this->expectNotToPerformAssertions();
        
        $this->privacyService->recordConsent(1, 'tracking', true);
    }

    public function test_has_conent_returns_boolean(): void
    {
        $hasConsent = $this->privacyService->hasConsent(1, 'tracking');
        
        $this->assertIsBool($hasConsent);
    }
}

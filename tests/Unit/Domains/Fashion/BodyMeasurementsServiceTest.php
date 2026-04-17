<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\BodyMeasurementsService;
use Tests\TestCase;

final class BodyMeasurementsServiceTest extends TestCase
{
    private BodyMeasurementsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BodyMeasurementsService();
    }

    public function test_calculate_figure_type_hourglass(): void
    {
        $measurements = [
            'bust' => 90,
            'waist' => 65,
            'hips' => 95,
        ];

        $result = $this->service->calculateFigureType($measurements);

        $this->assertEquals('hourglass', $result);
    }

    public function test_calculate_figure_type_pear(): void
    {
        $measurements = [
            'bust' => 85,
            'waist' => 75,
            'hips' => 100,
        ];

        $result = $this->service->calculateFigureType($measurements);

        $this->assertEquals('pear', $result);
    }

    public function test_calculate_bra_size(): void
    {
        $measurements = [
            'bust' => 90,
            'underbust' => 75,
        ];

        $result = $this->service->calculateBraSize($measurements);

        $this->assertStringContainsString('75', $result);
    }

    public function test_calculate_bmi_normal(): void
    {
        $measurements = [
            'height' => 165,
            'weight' => 60,
        ];

        $result = $this->service->calculateBMI($measurements);

        $this->assertEquals('normal', $result['status']);
        $this->assertEquals('Норма', $result['category']);
    }

    public function test_validate_measurements_returns_errors_for_invalid_values(): void
    {
        $measurements = [
            'height' => 100,
            'weight' => 200,
        ];

        $errors = $this->service->validateMeasurements($measurements);

        $this->assertArrayHasKey('height', $errors);
        $this->assertArrayHasKey('weight', $errors);
    }

    public function test_get_full_size_recommendations(): void
    {
        $measurements = [
            'height' => 165,
            'weight' => 60,
            'bust' => 90,
            'underbust' => 75,
            'waist' => 70,
            'hips' => 95,
        ];

        $result = $this->service->getFullSizeRecommendations($measurements);

        $this->assertArrayHasKey('figure_type', $result);
        $this->assertArrayHasKey('bra_size', $result);
        $this->assertArrayHasKey('bmi', $result);
    }
}

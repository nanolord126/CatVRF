<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\ML;

use Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionMannequinSizeAlgorithmServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionMannequinSizeAlgorithmService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionMannequinSizeAlgorithmService::class);
    }

    public function test_calculate_ideal_size(): void
    {
        $result = $this->service->calculateIdealSize(1, 1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommended_size', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    public function test_calculate_ideal_size_with_confidence(): void
    {
        $result = $this->service->calculateIdealSize(1, 1, 1);

        $this->assertArrayHasKey('confidence', $result);
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(1, $result['confidence']);
    }

    public function test_update_size_accuracy_feedback(): void
    {
        $result = $this->service->updateSizeAccuracy(1, 1, 1, 'M', 'perfect');

        $this->assertIsBool($result);
    }

    public function test_update_size_feedback_too_small(): void
    {
        $result = $this->service->updateSizeAccuracy(1, 1, 1, 'M', 'too_small');

        $this->assertIsBool($result);
    }

    public function test_update_size_feedback_too_large(): void
    {
        $result = $this->service->updateSizeAccuracy(1, 1, 1, 'M', 'too_large');

        $this->assertIsBool($result);
    }

    public function test_get_brand_size_accuracy(): void
    {
        $result = $this->service->getBrandSizeAccuracy(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_feedback', $result);
        $this->assertArrayHasKey('accuracy_percentage', $result);
    }

    public function test_train_size_model(): void
    {
        $this->service->trainSizeModel(1);

        $this->assertTrue(true); // If no exception thrown, test passes
    }

    public function test_calculate_ideal_measurements(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateIdealMeasurements');
        $method->setAccessible(true);

        $measurements = [
            'height' => 170,
            'chest' => 90,
            'waist' => 75,
        ];

        $result = $method->invoke($this->service, $measurements);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('chest', $result);
        $this->assertArrayHasKey('waist', $result);
        $this->assertArrayHasKey('hips', $result);
    }
}

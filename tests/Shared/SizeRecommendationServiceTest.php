<?php

declare(strict_types=1);

use App\Shared\Application\Services\SizeRecommendationService;
use App\Shared\Domain\Entities\SizeChart;
use function Pest\Laravel\{beforeEach};

beforeEach(function () {
    // Clean up test data
    SizeChart::query()->delete();
});

test('it recommends fashion size based on measurements', function () {
    // Setup test size chart
    SizeChart::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'brand' => null,
        'gender' => 'male',
        'region' => 'eu',
        'size_system' => 'international',
        'size_mappings' => [
            'international' => [
                'M' => [
                    'eu' => 50,
                    'us' => 40,
                    'uk' => 39,
                ],
            ],
        ],
        'measurements' => [
            'chest' => ['min' => 96, 'max' => 101],
            'waist' => ['min' => 81, 'max' => 86],
            'hips' => ['min' => 96, 'max' => 101],
        ],
        'category' => 'fashion',
        'is_active' => true,
    ]);

    $service = app(SizeRecommendationService::class);

    $recommendation = $service->recommendFashionSize(
        [
            'chest' => 98,
            'waist' => 83,
            'hips' => 98,
        ],
        'male',
        null
    );

    expect($recommendation)->not->toBeNull();
    expect($recommendation['category'])->toBe('fashion');
});

test('it recommends footwear size based on measurements', function () {
    // Setup test size chart
    SizeChart::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'brand' => null,
        'gender' => 'unisex',
        'region' => 'eu',
        'size_system' => 'eu',
        'size_mappings' => [
            'eu' => [
                42 => [
                    'us' => 9,
                    'uk' => 8,
                    'cm' => 26.5,
                ],
            ],
        ],
        'measurements' => [
            'foot_length' => ['min' => 26.0, 'max' => 26.5],
            'foot_width' => ['min' => 9.5, 'max' => 10.5],
        ],
        'category' => 'footwear',
        'is_active' => true,
    ]);

    $service = app(SizeRecommendationService::class);

    $recommendation = $service->recommendFootwearSize(
        [
            'foot_length' => ['min' => 26.2, 'max' => 26.3],
            'foot_width' => ['min' => 10.0, 'max' => 10.1],
        ],
        'unisex',
        null
    );

    expect($recommendation)->not->toBeNull();
    expect($recommendation['category'])->toBe('footwear');
});

test('it converts sizes between systems', function () {
    // Setup test size chart
    SizeChart::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'brand' => null,
        'gender' => 'male',
        'region' => 'eu',
        'size_system' => 'international',
        'size_mappings' => [
            'international' => [
                'M' => [
                    'eu' => 50,
                    'us' => 40,
                    'uk' => 39,
                ],
            ],
        ],
        'measurements' => [],
        'category' => 'fashion',
        'is_active' => true,
    ]);

    $service = app(SizeRecommendationService::class);

    $convertedSize = $service->convertSize('international', 'eu', 'M', 'fashion', 'male', null);

    expect($convertedSize)->toBe(50);
});

test('it logs size feedback for ML training', function () {
    $service = app(SizeRecommendationService::class);

    // This test verifies the method exists and can be called
    // Actual ML integration would be tested separately
    $service->logSizeFeedback(1, 'product-1', ['eu' => 50], ['eu' => 50], 'correct');

    // If no exception is thrown, the test passes
    expect(true)->toBeTrue();
});

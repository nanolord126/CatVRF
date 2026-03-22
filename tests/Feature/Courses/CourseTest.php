<?php

declare(strict_types=1);

use App\Domains\Courses\Models\Course;

test('course can be created', function () {
    $course = Course::factory()->create();

    expect($course->id)->toBeNumeric()
        ->and($course->tenant_id)->toBe(1)
        ->and($course->uuid)->toBeString()
        ->and($course->correlation_id)->toBeString();
});

test('enrollment service exists', function () {
    $service = app(\App\Domains\Courses\Services\EnrollmentService::class);

    expect($service)->toBeInstanceOf(\App\Domains\Courses\Services\EnrollmentService::class);
});

test('certificate service exists', function () {
    $service = app(\App\Domains\Courses\Services\CertificateService::class);

    expect($service)->toBeInstanceOf(\App\Domains\Courses\Services\CertificateService::class);
});

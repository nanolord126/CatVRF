<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use PHPUnit\Framework\TestCase;
use App\Domains\Education\Domain\Services\AICourseGeneratorService;

/**
 * Unit tests for AICourseGeneratorService.
 *
 * @covers \App\Domains\Education\Domain\Services\AICourseGeneratorService
 */
final class AICourseGeneratorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AICourseGeneratorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AICourseGeneratorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AICourseGeneratorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AICourseGeneratorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AICourseGeneratorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AICourseGeneratorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generate_course_structure_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AICourseGeneratorService::class, 'generateCourseStructure'),
            'AICourseGeneratorService must implement generateCourseStructure()'
        );
    }

    public function test_generate_lesson_content_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AICourseGeneratorService::class, 'generateLessonContent'),
            'AICourseGeneratorService must implement generateLessonContent()'
        );
    }

    public function test_generate_quiz_for_student_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AICourseGeneratorService::class, 'generateQuizForStudent'),
            'AICourseGeneratorService must implement generateQuizForStudent()'
        );
    }
}

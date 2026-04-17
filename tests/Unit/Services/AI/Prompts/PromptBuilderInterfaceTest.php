<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI\Prompts;

use App\Services\AI\Prompts\PromptBuilderInterface;
use App\Services\AI\Prompts\TaxiRoutePromptBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for PromptBuilder interface and implementations
 */
final class PromptBuilderInterfaceTest extends TestCase
{
    private TaxiRoutePromptBuilder $promptBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promptBuilder = new TaxiRoutePromptBuilder();
    }

    public function test_implements_prompt_builder_interface(): void
    {
        $this->assertInstanceOf(PromptBuilderInterface::class, $this->promptBuilder);
    }

    public function test_get_system_prompt_returns_string(): void
    {
        $prompt = $this->promptBuilder->getSystemPrompt();

        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
        $this->assertStringContainsString('такси', strtolower($prompt));
    }

    public function test_get_system_prompt_with_context(): void
    {
        $context = [
            'vertical' => 'taxi',
            'type' => 'route_optimization',
        ];

        $prompt = $this->promptBuilder->getSystemPrompt($context);

        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
    }

    public function test_get_user_prompt_returns_string(): void
    {
        $context = [
            'pickup_location' => 'Moscow, Red Square',
            'dropoff_location' => 'Moscow, Kremlin',
            'ride_type' => 'economy',
            'passengers' => 2,
        ];

        $prompt = $this->promptBuilder->getUserPrompt($context);

        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
        $this->assertStringContainsString('Red Square', $prompt);
        $this->assertStringContainsString('Kremlin', $prompt);
        $this->assertStringContainsString('economy', $prompt);
    }

    public function test_get_user_prompt_interpolates_context(): void
    {
        $context = [
            'pickup_location' => 'Test Location A',
            'dropoff_location' => 'Test Location B',
            'ride_type' => 'comfort',
            'passengers' => 3,
        ];

        $prompt = $this->promptBuilder->getUserPrompt($context);

        $this->assertStringContainsString('Test Location A', $prompt);
        $this->assertStringContainsString('Test Location B', $prompt);
        $this->assertStringContainsString('comfort', $prompt);
        $this->assertStringContainsString('3', $prompt);
    }

    public function test_get_output_schema_returns_array(): void
    {
        $schema = $this->promptBuilder->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
    }

    public function test_output_schema_has_required_fields(): void
    {
        $schema = $this->promptBuilder->getOutputSchema();

        $this->assertArrayHasKey('route', $schema['properties']);
        $this->assertArrayHasKey('pricing', $schema['properties']);
        $this->assertArrayHasKey('driver_criteria', $schema['properties']);
        $this->assertArrayHasKey('eta', $schema['properties']);
        $this->assertArrayHasKey('required', $schema);
    }

    public function test_get_version_returns_string(): void
    {
        $version = $this->promptBuilder->getVersion();

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    public function test_get_metadata_returns_array(): void
    {
        $metadata = $this->promptBuilder->getMetadata();

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('version', $metadata);
        $this->assertArrayHasKey('class', $metadata);
        $this->assertArrayHasKey('vertical', $metadata);
        $this->assertArrayHasKey('type', $metadata);
    }

    public function test_metadata_contains_correct_values(): void
    {
        $metadata = $this->promptBuilder->getMetadata();

        $this->assertEquals('taxi', $metadata['vertical']);
        $this->assertEquals('route_optimization', $metadata['type']);
        $this->assertEquals(TaxiRoutePromptBuilder::class, $metadata['class']);
    }

    public function test_system_prompt_sanitizes_potential_attacks(): void
    {
        // Test that potentially harmful patterns are removed
        $context = ['vertical' => 'taxi'];
        $prompt = $this->promptBuilder->getSystemPrompt($context);

        // Should not contain script tags
        $this->assertStringNotContainsString('<script', $prompt);
        // Should not contain javascript: protocol
        $this->assertStringNotContainsString('javascript:', strtolower($prompt));
    }
}

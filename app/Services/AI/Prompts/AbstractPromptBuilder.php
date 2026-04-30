<?php

declare(strict_types=1);

namespace App\Services\AI\Prompts;

use LogManager;

use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * Abstract base class for prompt builders
 *
 * Provides common functionality for all prompt builders:
 * - Version tracking
 * - Metadata management
 * - Logging
 * - Context interpolation
 */
abstract class AbstractPromptBuilder implements PromptBuilderInterface
{
    protected readonly string $version = '1.0.0';

    protected readonly array $metadata = [];

    protected readonly LogManager $logManager;

    public function __construct()
    {
        $this->logManager = $this->logManager;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getMetadata(): array
    {
        return array_merge([
            'version' => $this->version,
            'class' => static::class,
            'created_at' => CarbonImmutable::now()->toIso8601String(),
        ], $this->metadata);
    }

    /**
     * Interpolate context variables into template
     *
     * @param  string  $template  Template with {{variable}} placeholders
     * @param  array  $context  Variables to interpolate
     */
    protected function interpolate(string $template, array $context = []): string
    {
        foreach ($context as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $template = str_replace($placeholder, (string) $value, $template);
        }

        return $template;
    }

    /**
     * Sanitize prompt to prevent injection attacks
     */
    protected function sanitize(string $prompt): string
    {
        // Remove potentially harmful patterns
        $prompt = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $prompt);
        $prompt = preg_replace('/javascript:/i', '', $prompt);

        return $prompt;
    }

    /**
     * Log prompt usage for monitoring
     */
    protected function logUsage(string $promptType, array $context = []): void
    {
        $this->logManager->debug('Prompt builder used', [
            'builder_class' => static::class,
            'prompt_type' => $promptType,
            'version' => $this->version,
            'context_keys' => array_keys($context),
        ]);
    }
}

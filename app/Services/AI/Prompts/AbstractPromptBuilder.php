<?php

declare(strict_types=1);

namespace App\Services\AI\Prompts;

use Illuminate\Support\Facades\Log;

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
    protected string $version = '1.0.0';
    protected array $metadata = [];

    /**
     * Interpolate context variables into template
     * 
     * @param string $template Template with {{variable}} placeholders
     * @param array $context Variables to interpolate
     * @return string
     */
    protected function interpolate(string $template, array $context = []): string
    {
        foreach ($context as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $template = str_replace($placeholder, (string) $value, $template);
        }

        return $template;
    }

    /**
     * Sanitize prompt to prevent injection attacks
     * 
     * @param string $prompt
     * @return string
     */
    protected function sanitize(string $prompt): string
    {
        // Remove potentially harmful patterns
        $prompt = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $prompt);
        $prompt = preg_replace('/javascript:/i', '', $prompt);
        
        return $prompt;
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
            'created_at' => now()->toIso8601String(),
        ], $this->metadata);
    }

    /**
     * Log prompt usage for monitoring
     * 
     * @param string $promptType
     * @param array $context
     * @return void
     */
    protected function logUsage(string $promptType, array $context = []): void
    {
        Log::debug('Prompt builder used', [
            'builder_class' => static::class,
            'prompt_type' => $promptType,
            'version' => $this->version,
            'context_keys' => array_keys($context),
        ]);
    }
}

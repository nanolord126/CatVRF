<?php

declare(strict_types=1);

namespace App\Services\AI\Prompts;

/**
 * Interface for AI prompt builders
 * 
 * Separates prompt construction logic from service logic.
 * Each vertical should implement its own PromptBuilder.
 * 
 * Benefits:
 * - Easy prompt updates without regenerating services
 * - Version control for prompts
 * - A/B testing of prompts
 * - Centralized prompt management
 */
interface PromptBuilderInterface
{
    /**
     * Get the system prompt for the AI model
     * 
     * @param array $context Additional context for the prompt
     * @return string
     */
    public function getSystemPrompt(array $context = []): string;

    /**
     * Get the user prompt template
     * 
     * @param array $context Additional context for the prompt
     * @return string
     */
    public function getUserPrompt(array $context = []): string;

    /**
     * Get the structured output schema for the AI response
     * 
     * @return array JSON schema for structured output
     */
    public function getOutputSchema(): array;

    /**
     * Get prompt version for tracking
     * 
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get prompt metadata (description, parameters, etc.)
     * 
     * @return array
     */
    public function getMetadata(): array;
}

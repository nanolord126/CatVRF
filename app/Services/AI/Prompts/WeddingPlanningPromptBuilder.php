<?php

declare(strict_types=1);

namespace App\Services\AI\Prompts;

/**
 * Prompt builder for WeddingPlanning AI
 * 
 * Vertical: weddingplanning
 * Type: ai_constructor
 * 
 * Generates prompts for AI-powered features in WeddingPlanning vertical.
 */
final class WeddingPlanningPromptBuilder extends AbstractPromptBuilder
{
    protected string $version = '1.0.0';
    protected array $metadata = [
        'vertical' => 'weddingplanning',
        'type' => 'ai_constructor',
        'description' => 'AI constructor for WeddingPlanning vertical',
        'language' => 'ru',
    ];

    public function getSystemPrompt(array $context = []): string
    {
        $prompt = <<<PROMPT
Ты — эксперт в вертикали WeddingPlanning. 
Твоя задача — анализировать данные и предоставлять качественные рекомендации.

Контекст:
- Вертикаль: weddingplanning
- Тип: ai_constructor
- Язык ответа: русский

Правила:
1. Всегда учитывай контекст запроса
2. Предоставляй структурированные ответы
3. Используй фактические данные
PROMPT;

        $this->logUsage('system', $context);

        return $this->sanitize($prompt);
    }

    public function getUserPrompt(array $context = []): string
    {
        $prompt = <<<PROMPT
Проанализируй следующие данные:

{{context_data}}

Предоставь рекомендации в структурированном формате.
PROMPT;

        $interpolated = $this->interpolate($prompt, [
            'context_data' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);

        $this->logUsage('user', $context);

        return $this->sanitize($interpolated);
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'object'],
                ],
                'confidence' => ['type' => 'number'],
                'metadata' => ['type' => 'object'],
            ],
            'required' => ['recommendations', 'confidence'],
        ];
    }
}
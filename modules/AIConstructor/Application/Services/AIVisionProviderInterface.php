<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Application\Services;

/**
 * Исключительно абстрактный порт (Interface) для внешних провайдеров нейросетевых vision-моделей
 * (OpenAI Vision, GigaChat, Stable Diffusion).
 *
 * Категорически следует принципу Dependency Inversion, защищая ядро приложения от жесткой привязки
 * к синтаксису и API конкретных AI-вендоров.
 */
interface AIVisionProviderInterface
{
    /**
     * Строго передает фотографию пользователя и целевой промпт внешней нейросети для глубокого анализа
     * и генерации полиморфного ответа в соответствии с бизнес-логикой вертикали.
     *
     * @param string $photoPath Безупречный абсолютный путь к временно сохраненному файлу фотографии.
     * @param string $systemPrompt Инженерный промпт, категорически определяющий стиль поведения LLM.
     * @return array<string, mixed> Универсальный распознанный набор признаков и сгенерированных рекомендаций.
     */
    public function analyzeAndGenerate(string $photoPath, string $systemPrompt): array;
}

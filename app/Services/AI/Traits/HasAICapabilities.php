<?php declare(strict_types=1);

namespace App\Services\AI\Traits;

use App\Services\AI\OpenAIClientService;
use Psr\Log\LoggerInterface;

trait HasAICapabilities
{
    protected OpenAIClientService $openai;
    protected LoggerInterface $logger;

    /**
     * Инициализация OpenAI клиента (должен быть вызван в конструкторе)
     */
    protected function initializeAIClient(OpenAIClientService $openai): void
    {
        $this->openai = $openai;
    }

    /**
     * Инициализация Logger (должен быть вызван в конструкторе)
     */
    protected function initializeLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Анонимизация персональных данных перед отправкой в OpenAI
     */
    protected function anonymizeData(string $data): string
    {
        $patterns = [
            '/\b[A-ZА-Я][a-zа-я]+\s+[A-ZА-Я][a-zа-я]+\b/' => '[ИМЯ ФАМИЛИЯ]',
            '/\b\d{2}\.\d{2}\.\d{4}\b/' => '[ДАТА]',
            '/\b\d{11}\b/' => '[ТЕЛЕФОН]',
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            '/\b\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\b/' => '[КАРТА]',
            '/\b\d{14}\b/' => '[СНИЛС]',
            '/\b\d{16}\b/' => '[ИНН]',
            '/\b\d{20}\b/' => '[СЧЕТ]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $data);
    }

    /**
     * Безопасный вызов OpenAI chat с анонимизацией
     */
    protected function callOpenAIChat(array $messages, float $temperature = 0.3, string $responseFormat = 'text', ?string $correlationId = null): array
    {
        if (!$this->openai->isEnabled()) {
            throw new \RuntimeException('OpenAI service is not configured.');
        }

        // Анонимизация сообщений
        $anonymizedMessages = array_map(function ($message) {
            return [
                'role' => $message['role'],
                'content' => $this->anonymizeData($message['content']),
            ];
        }, $messages);

        try {
            $response = $this->openai->chat($anonymizedMessages, $temperature, $responseFormat);
        } catch (\Throwable $e) {
            if ($correlationId !== null) {
                $this->logger->error('OpenAI API call failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
            throw new \RuntimeException('Failed to get AI response. Please try again later.');
        }

        return $response;
    }
}

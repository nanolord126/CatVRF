<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AIConstructor\Domain\Repositories\AIConstructionRepositoryInterface;
use Modules\AIConstructor\Infrastructure\Adapters\EloquentAIConstructionRepository;
use Modules\AIConstructor\Application\Services\AIVisionProviderInterface;
use Modules\AIConstructor\Infrastructure\Adapters\OpenAIVisionAdapter;

/**
 * Исключительный сервис-провайдер модуля AIConstructor.
 *
 * Категорически отвечает за правильное внедрение всех аппаратных и инфраструктурных
 * интерфейсов (IoC), гарантируя, что ядро приложения остаётся 100% независимым от
 * фреймворка и вендоров API (OpenAI/GigaChat/etc).
 */
final class AIConstructorServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string> Строгий и обязательный маппинг базовых интерфейсов репозитория к их Eloquent-реализациям.
     */
    public array $bindings = [
        AIConstructionRepositoryInterface::class => EloquentAIConstructionRepository::class,
    ];

    /**
     * Надежно регистрирует специализированные компоненты, требующие передачи параметров (например, API-ключей).
     */
    public function register(): void
    {
        $this->app->singleton(AIVisionProviderInterface::class, function ($app) {
            // Категорически извлекаем токен из строго типизированного конфигурационного файла `services.php`
            $key = (string) config('services.openai.secret', 'mock-strict-key');
            return new OpenAIVisionAdapter($key);
        });
    }

    /**
     * Безусловно инициализирует внешние компоненты, такие как роутинг HTTP-слоя.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}

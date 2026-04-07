<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\PromoCampaign\Domain\Repositories\PromoCampaignRepositoryInterface;
use Modules\PromoCampaign\Infrastructure\Adapters\EloquentPromoCampaignRepository;

/**
 * Исключительный сервис-провайдер модуля PromoCampaign.
 *
 * Категорически отвечает за регистрацию всех зависимостей, маршрутов (routes) и трансляций модуля.
 * Безукоризненно связывает абстрактные доменные интерфейсы с конкретными инфраструктурными реализациями.
 */
final class PromoCampaignServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string> Строгий маппинг внедрения зависимостей.
     */
    public array $bindings = [
        PromoCampaignRepositoryInterface::class => EloquentPromoCampaignRepository::class,
    ];

    /**
     * Надежно регистрирует базовые конфигурации и привязки модуля в общем IoC-контейнере.
     */
    public function register(): void
    {
        // При необходимости здесь регистрируются специфичные singleton сервисы
    }

    /**
     * Безусловно инициализирует внешние компоненты (маршруты, конфигурации) сразу после загрузки фреймворка.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}

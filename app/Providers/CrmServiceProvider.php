<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\CRM\Services\AutoCrmService;
use App\Domains\CRM\Services\BeautyCrmService;
use App\Domains\CRM\Services\CrmAnalyticsService;
use App\Domains\CRM\Services\CrmAutomationService;
use App\Domains\CRM\Services\CrmSegmentationService;
use App\Domains\CRM\Services\CrmService;
use App\Domains\CRM\Services\EducationCrmService;
use App\Domains\CRM\Services\ElectronicsCrmService;
use App\Domains\CRM\Services\EventsCrmService;
use App\Domains\CRM\Services\FashionCrmService;
use App\Domains\CRM\Services\FitnessCrmService;
use App\Domains\CRM\Services\FlowerCrmService;
use App\Domains\CRM\Services\FoodCrmService;
use App\Domains\CRM\Services\FurnitureCrmService;
use App\Domains\CRM\Services\HotelCrmService;
use App\Domains\CRM\Services\MedicalCrmService;
use App\Domains\CRM\Services\PetCrmService;
use App\Domains\CRM\Services\RealEstateCrmService;
use App\Domains\CRM\Services\TaxiCrmService;
use App\Domains\CRM\Services\TravelCrmService;
use Illuminate\Support\ServiceProvider;

/**
 * CrmServiceProvider — регистрация всех CRM-сервисов.
 *
 * Каждый сервис регистрируется как singleton, т.к. содержит readonly-зависимости
 * и не хранит mutable state между запросами.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmServiceProvider extends ServiceProvider
{
    /**
     * Основные (core) CRM-сервисы.
     *
     * @var array<int, class-string>
     */
    private const CORE_SERVICES = [
        CrmService::class,
        CrmSegmentationService::class,
        CrmAutomationService::class,
        CrmAnalyticsService::class,
    ];

    /**
     * Вертикальные CRM-сервисы.
     *
     * @var array<string, class-string>
     */
    private const VERTICAL_SERVICES = [
        'beauty' => BeautyCrmService::class,
        'hotel' => HotelCrmService::class,
        'flowers' => FlowerCrmService::class,
        'auto' => AutoCrmService::class,
        'food' => FoodCrmService::class,
        'furniture' => FurnitureCrmService::class,
        'fashion' => FashionCrmService::class,
        'fitness' => FitnessCrmService::class,
        'real_estate' => RealEstateCrmService::class,
        'medical' => MedicalCrmService::class,
        'education' => EducationCrmService::class,
        'travel' => TravelCrmService::class,
        'pet' => PetCrmService::class,
        'taxi' => TaxiCrmService::class,
        'electronics' => ElectronicsCrmService::class,
        'events' => EventsCrmService::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/crm.php',
            'crm',
        );

        foreach (self::CORE_SERVICES as $service) {
            $this->app->singleton($service);
        }

        foreach (self::VERTICAL_SERVICES as $vertical => $service) {
            $this->app->singleton($service);
        }

        $this->app->singleton('crm.verticals', function (): array {
            return self::VERTICAL_SERVICES;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/crm.php' => config_path('crm.php'),
        ], 'crm-config');
    }

    /**
     * Получить CRM-сервис по имени вертикали.
     */
    public static function resolveVerticalService(string $vertical): object
    {
        $map = self::VERTICAL_SERVICES;

        if (!isset($map[$vertical])) {
            throw new \InvalidArgumentException("CRM service for vertical '{$vertical}' is not configured.");
        }

        return app($map[$vertical]);
    }
}

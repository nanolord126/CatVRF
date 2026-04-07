<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Infrastructure\Providers;

use App\Domains\Hotels\Domain\Repositories\BookingRepositoryInterface;
use App\Domains\Hotels\Domain\Repositories\HotelRepositoryInterface;
use App\Domains\Hotels\Domain\Repositories\RoomRepositoryInterface;
use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Repositories\EloquentBookingRepository;
use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Repositories\EloquentHotelRepository;
use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoomRepository;
use App\Domains\Hotels\Presentation\Http\Controllers\HotelController;
use App\Domains\Hotels\Presentation\Livewire\SearchHotels;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;

/**
 * HotelsServiceProvider — Поставщик сервисов домена Hotels.
 *
 * Отвечает за:
 * - Привязку интерфейсов репозиториев к их Eloquent-реализациям
 * - Привязку канала audit-логгера к HotelController без статических фасадов
 * - Регистрацию Livewire-компонента SearchHotels
 * - Загрузку маршрутов B2C API для домена
 * - Загрузку миграций, если они ещё не запущены
 *
 * @package App\Domains\Hotels\Infrastructure\Providers
 */
final class HotelsServiceProvider extends ServiceProvider
{
    /**
     * Все биндинги поставщика (interface => concrete).
     *
     * @var array<string, string>
     */
    public array $bindings = [
        HotelRepositoryInterface::class   => EloquentHotelRepository::class,
        RoomRepositoryInterface::class    => EloquentRoomRepository::class,
        BookingRepositoryInterface::class => EloquentBookingRepository::class,
    ];

    /**
     * Регистрирует зависимости в IoC-контейнер фреймворка.
     *
     * Здесь происходит только лёгкая регистрация зависимостей.
     * Тяжёлые инициализации (пути, миграции) вынесены в boot().
     */
    public function register(): void
    {
        /**
         * Привязываем канал audit логгера к HotelController.
         * HotelController получает LoggerInterface через конструктор без статических фасадов.
         */
        $this->app
            ->when(HotelController::class)
            ->needs(LoggerInterface::class)
            ->give(fn (Application $app): LoggerInterface => $app->make('log')->channel('audit'));
    }

    /**
     * Выполняет тяжёлые инициализации после регистрации всех поставщиков.
     * Загружает маршруты, миграции, регистрирует Livewire-компоненты.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            database_path('migrations')
        );

        $this->loadRoutesIfFileExists(
            base_path('routes/hotels-api.php')
        );

        $this->registerLivewireComponents();
    }

    /**
     * Регистрирует Livewire-компоненты домена Hotels.
     * Проверяет наличие Livewire, чтобы не падать в non-Livewire средах.
     */
    private function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component('hotels.search-hotels', SearchHotels::class);
    }

    /**
     * Загружает файл маршрутов, если он существует.
     * Позволяет не падать с ошибкой, если файл маршрутов ещё не создан.
     */
    private function loadRoutesIfFileExists(string $path): void
    {
        if (file_exists($path)) {
            $this->loadRoutesFrom($path);
        }
    }
}

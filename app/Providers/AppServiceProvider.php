<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\BeautyMasters\Models\Appointment;
use Modules\Hotels\Models\Booking;
use Modules\Hotels\Observers\BookingObserver;
use Modules\Inventory\Listeners\DeductAppointmentConsumables;
use App\Models\MedicalCard;
use App\Observers\Clinic\MedicalCardObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\Infrastructure\DopplerService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DopplerService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Doppler-based secret injection (Zero Trust 2026)
        $this->app->make(DopplerService::class)->boot();

        $this->configureRateLimiting();

        Appointment::created(function (Appointment $appointment) {
            (new DeductAppointmentConsumables())->handle($appointment);
        });

        Booking::observe(BookingObserver::class);
        
        // Регистрация обсерватора для медицинских карт — автоматизация чеклистов
        MedicalCard::observe(MedicalCardObserver::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // 1. Стандартный лимит для API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 2. Лимит на платежи (SBP/Atol/Wallet) - защита от брутфорса и дублей
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // 3. Лимит на массовые рассылки и уведомления
        RateLimiter::for('notifications', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // 4. Лимит на импорт данных (Zero Trust Import)
        RateLimiter::for('import', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });
    }
}


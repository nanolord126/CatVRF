<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ─── Global Jobs ─────────────────────────────────────────────────────────────
use App\Jobs\AggregateDailyAnalyticsJob;
use App\Jobs\BonusAccrualJob;
use App\Jobs\CleanupExpiredBonusesJob;
use App\Jobs\CleanupExpiredIdempotencyRecordsJob;
use App\Jobs\CleanupStaleCollaborationSessionsJob;
use App\Jobs\DemandForecastJob;
use App\Jobs\FraudMLRecalculationJob;
use App\Jobs\LowStockNotificationJob;
use App\Jobs\PayoutProcessingJob;
use App\Jobs\RecalculateAnalyticsJob;
use App\Jobs\RecommendationQualityJob;
use App\Jobs\ReleaseHoldJob;

// ─── Domain Jobs ─────────────────────────────────────────────────────────────
use App\Domains\Beauty\Jobs\AppointmentReminderJob;
use App\Domains\Content\Channels\Jobs\ArchiveInactiveChannelsJob;
use App\Domains\Content\Channels\Jobs\PostSchedulerJob;
use App\Domains\Content\Channels\Jobs\SubscriptionRenewalJob;
use App\Domains\Education\Courses\Jobs\CertificateGenerationJob;
use App\Domains\Education\Courses\Jobs\EnrollmentReminderJob;
use App\Domains\EventPlanning\Entertainment\Jobs\CalculateEntertainerEarningsJob;
use App\Domains\EventPlanning\Entertainment\Jobs\SendEventReminderJob;
use App\Domains\Fashion\Jobs\CalculateStoreEarningsJob;
use App\Domains\Fashion\Jobs\UpdateOrderStatusJob;
use App\Domains\Sports\Fitness\Jobs\CalculateTrainerEarningsJob;
use App\Domains\Sports\Fitness\Jobs\SendClassReminderJob;
use App\Domains\Flowers\Jobs\CalculateFlowerShopEarningsJob;
use App\Domains\Flowers\Jobs\ProcessFlowerOrderStatusJob;
use App\Domains\Food\Jobs\AutoCloseOrderJob;
use App\Domains\Food\Jobs\OrderReadyReminderJob;
use App\Domains\Freelance\Jobs\CalculateFreelancerEarningsJob;
use App\Domains\Freelance\Jobs\UpdateDeliverableStatusJob;
use App\Domains\HomeServices\Jobs\CalculateContractorEarningsJob;
use App\Domains\HomeServices\Jobs\SendJobReminderJob;
use App\Domains\Hotels\Jobs\AutoCheckOutJob;
use App\Domains\Hotels\Jobs\CheckInReminderJob;
use App\Domains\Logistics\Jobs\CalculateCourierEarningsJob;
use App\Domains\Logistics\Jobs\UpdateShipmentStatusJob;
use App\Domains\Medical\Jobs\CalculateClinicEarningsJob;
use App\Domains\Medical\Jobs\UpdateAppointmentStatusJob;
use App\Domains\Pet\Jobs\CalculateClinicEarningsJob as PetCalculateClinicEarningsJob;
use App\Domains\Pet\Jobs\UpdateAppointmentStatusJob as PetUpdateAppointmentStatusJob;
use App\Domains\Photography\Jobs\CalculateRatingsJob;
use App\Domains\Photography\Jobs\UpdateSessionStatusJob;
use App\Domains\RealEstate\Jobs\PropertyAutoCloseJob;
use App\Domains\RealEstate\Jobs\ViewingReminderJob;
use App\Domains\Sports\Jobs\BookingConfirmationJob;
use App\Domains\Sports\Jobs\ClassReminderJob;
use App\Domains\Taxi\Jobs\RideReminderJob;
use App\Domains\Taxi\Jobs\SurgeRecalculationJob;
use App\Domains\Tickets\Jobs\EventReminderJob;
use App\Domains\Tickets\Jobs\TicketGenerationJob;
use App\Domains\Travel\Jobs\CalculateAgencyEarningsJob;
use App\Domains\Travel\Jobs\UpdateBookingStatusJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('videocall:cleanup')->daily();

// ══════════════════════════════════════════════════════════════════════════════
// GLOBAL PLATFORM JOBS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Единое время проверки для всех тенантов — 03:00 по их местному времени.
 * Этот цикл проходит по всем тенантам, проверяет текущий час в их timezone
 * и запускает необходимые задачи, если сейчас 03:00.
 */
Schedule::call(function () {
    \App\Models\Tenant::query()
        ->where('is_active', true)
        ->each(function (\App\Models\Tenant $tenant) {
            $timezone = $tenant->timezone ?? 'UTC';
            $localHour = now($timezone)->hour;

            if ($localHour === 3) {
                $correlationId = \Illuminate\Support\Str::uuid()->toString();

                // 1. Агрегация аналитики
                AggregateDailyAnalyticsJob::dispatch($tenant->id, $correlationId);

                // 2. ML-фрод: переобучение (модели тенанта)
                FraudMLRecalculationJob::dispatch($tenant->id, $correlationId);

                // 3. Прогноз спроса
                DemandForecastJob::dispatch($tenant->id, $correlationId);

                // 4. Качество рекомендаций
                RecommendationQualityJob::dispatch($tenant->id, $correlationId);

                // 5. Обработка выплат
                PayoutProcessingJob::dispatch($tenant->id, $correlationId);

                // 6. Разморозка бонусов (Cooling period)
                \App\Jobs\Bonus\BonusUnlockJob::dispatch();

                // 7. Очистка истёкших бонусов
                CleanupExpiredBonusesJob::dispatch($tenant->id, $correlationId);

                Log::channel('audit')->info('Daily schedule (03:00 local) triggered for tenant', [
                    'tenant_id' => $tenant->id,
                    'timezone' => $timezone,
                    'correlation_id' => $correlationId,
                ]);
            }
        });
})->hourly()->name('platform.daily-maintenance-synchronized');

// Пересчёт аналитики — каждый час (dispatches per-tenant)
Schedule::call(function () {
    \App\Models\Tenant::query()->pluck('id')->each(function (int $tenantId) {
        RecalculateAnalyticsJob::dispatch($tenantId);
    });
})->hourly()->name('platform.recalculate-analytics');

// Уведомления о низком остатке — 08:00 UTC (оставляем фиксированным для утра)
Schedule::job(new LowStockNotificationJob())
    ->dailyAt('08:00')
    ->name('platform.low-stock-notification')
    ->withoutOverlapping(60);

// Очистка истёкших idempotency records — каждый день 00:30 UTC
Schedule::job(new CleanupExpiredIdempotencyRecordsJob())
    ->dailyAt('00:30')
    ->name('platform.cleanup-idempotency-records')
    ->withoutOverlapping(30);

// Очистка устаревших сессий совместной работы — еженедельно
Schedule::job(new CleanupStaleCollaborationSessionsJob())
    ->weeklyOn(1, '03:00')
    ->name('platform.cleanup-collaboration-sessions')
    ->withoutOverlapping(60);

// Снятие холдов с истёкшими сроками — каждые 15 минут
Schedule::job(new ReleaseHoldJob())
    ->everyFifteenMinutes()
    ->name('platform.release-hold')
    ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// TAXI / AUTO — Surge каждые 5 минут
// ══════════════════════════════════════════════════════════════════════════════
Schedule::call(fn () => SurgeRecalculationJob::dispatch())
    ->everyFiveMinutes()
    ->name('taxi.surge-recalculation');

// Закомментировано: требует параметры
// Schedule::job(new RideReminderJob())
//     ->everyFifteenMinutes()
//     ->name('taxi.ride-reminder')
//     ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// BEAUTY
// ══════════════════════════════════════════════════════════════════════════════
// Закомментировано: требует параметры Appointment
// Schedule::job(new AppointmentReminderJob())
//     ->everyThirtyMinutes()
//     ->name('beauty.appointment-reminder')
//     ->withoutOverlapping(25);

Schedule::call(fn () => SendAppointmentRemindersJob::dispatch())
    ->everyThirtyMinutes()
    ->name('beauty.appointment-reminders');

// ══════════════════════════════════════════════════════════════════════════════
// CHANNELS
// ══════════════════════════════════════════════════════════════════════════════



// ══════════════════════════════════════════════════════════════════════════════
// COURSES
// ══════════════════════════════════════════════════════════════════════════════
// Закомментировано: требует параметры
// Schedule::job(new CertificateGenerationJob())
//     ->hourly()
//     ->name('courses.certificate-generation')
//     ->withoutOverlapping(55);

// Schedule::job(new EnrollmentReminderJob())
//     ->dailyAt('08:30')
//     ->name('courses.enrollment-reminder')
//     ->withoutOverlapping(30);

// ══════════════════════════════════════════════════════════════════════════════
// ENTERTAINMENT
// ══════════════════════════════════════════════════════════════════════════════


// ══════════════════════════════════════════════════════════════════════════════
// FASHION
// ══════════════════════════════════════════════════════════════════════════════


// ══════════════════════════════════════════════════════════════════════════════
// FITNESS
// ══════════════════════════════════════════════════════════════════════════════


// ══════════════════════════════════════════════════════════════════════════════
// FLOWERS
// ══════════════════════════════════════════════════════════════════════════════


// ══════════════════════════════════════════════════════════════════════════════
// FOOD
// ══════════════════════════════════════════════════════════════════════════════
// Schedule::job(new AutoCloseOrderJob())
//     ->everyFifteenMinutes()
//     ->name('food.auto-close-order')
//     ->withoutOverlapping(10);

// Schedule::job(new OrderReadyReminderJob())
//     ->everyFifteenMinutes()
//     ->name('food.order-ready-reminder')
//     ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// FREELANCE
// ══════════════════════════════════════════════════════════════════════════════
// Schedule::job(new CalculateFreelancerEarningsJob())
//     ->dailyAt('04:30')
//     ->name('freelance.calculate-earnings')
//     ->withoutOverlapping(60);

// Schedule::job(new UpdateDeliverableStatusJob())
//     ->hourly()
//     ->name('freelance.update-deliverable-status')
//     ->withoutOverlapping(55);

// ══════════════════════════════════════════════════════════════════════════════
// HOME SERVICES
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateContractorEarningsJob())
    ->dailyAt('05:00')
    ->name('home-services.calculate-earnings')
    ->withoutOverlapping(60);

Schedule::job(new SendJobReminderJob())
    ->everyThirtyMinutes()
    ->name('home-services.job-reminder')
    ->withoutOverlapping(25);

// ══════════════════════════════════════════════════════════════════════════════
// HOTELS
// ══════════════════════════════════════════════════════════════════════════════
/*
Schedule::job(new AutoCheckOutJob())
    ->hourly()
    ->name('hotels.auto-checkout')
    ->withoutOverlapping(55);

Schedule::job(new CheckInReminderJob())
    ->dailyAt('09:00')
    ->name('hotels.check-in-reminder')
    ->withoutOverlapping(30);
*/

// ══════════════════════════════════════════════════════════════════════════════
// LOGISTICS
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateCourierEarningsJob())
    ->dailyAt('05:30')
    ->name('logistics.calculate-earnings')
    ->withoutOverlapping(60);

// Schedule::job(new UpdateShipmentStatusJob())
//     ->everyFifteenMinutes()
//     ->name('logistics.update-shipment-status')
//     ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// MEDICAL
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateClinicEarningsJob())
    ->dailyAt('06:00')
    ->name('medical.calculate-earnings')
    ->withoutOverlapping(60);

// Schedule::job(new UpdateAppointmentStatusJob())
//     ->everyThirtyMinutes()
//     ->name('medical.update-appointment-status')
//     ->withoutOverlapping(25);

// ══════════════════════════════════════════════════════════════════════════════
// PET
// ══════════════════════════════════════════════════════════════════════════════
// Schedule::job(new PetCalculateClinicEarningsJob())
//     ->dailyAt('06:30')
//     ->name('pet.calculate-earnings')
//     ->withoutOverlapping(60);

// Schedule::job(new PetUpdateAppointmentStatusJob())
//     ->everyThirtyMinutes()
//     ->name('pet.update-appointment-status')
//     ->withoutOverlapping(25);

// ══════════════════════════════════════════════════════════════════════════════
// PHOTOGRAPHY
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateRatingsJob())
    ->dailyAt('07:00')
    ->name('photography.calculate-ratings')
    ->withoutOverlapping(60);

// Schedule::job(new UpdateSessionStatusJob())
//     ->hourly()
//     ->name('photography.update-session-status')
//     ->withoutOverlapping(55);

// ══════════════════════════════════════════════════════════════════════════════
// REAL ESTATE
// ══════════════════════════════════════════════════════════════════════════════
// Schedule::job(new PropertyAutoCloseJob())
//     ->dailyAt('07:30')
//     ->name('realestate.property-auto-close')
//     ->withoutOverlapping(60);

// Schedule::job(new ViewingReminderJob())
//     ->hourly()
//     ->name('realestate.viewing-reminder')
//     ->withoutOverlapping(55);

// ══════════════════════════════════════════════════════════════════════════════
// SPORTS
// ══════════════════════════════════════════════════════════════════════════════
// Schedule::job(new BookingConfirmationJob())
//     ->everyThirtyMinutes()
//     ->name('sports.booking-confirmation')
//     ->withoutOverlapping(25);

// Schedule::job(new ClassReminderJob())
//     ->everyThirtyMinutes()
//     ->name('sports.class-reminder')
//     ->withoutOverlapping(25);

// ══════════════════════════════════════════════════════════════════════════════
// TICKETS
// ══════════════════════════════════════════════════════════════════════════════
// Schedule::job(new EventReminderJob())
//     ->hourly()
//     ->name('tickets.event-reminder')
//     ->withoutOverlapping(55);

// Schedule::job(new TicketGenerationJob())
//     ->everyFifteenMinutes()
//     ->name('tickets.ticket-generation')
//     ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// TRAVEL
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateAgencyEarningsJob(1, 'sch-'.\Illuminate\Support\Str::uuid()->toString()))
    ->dailyAt('08:00')
    ->name('travel.calculate-earnings')
    ->withoutOverlapping(60);

Schedule::job(new UpdateBookingStatusJob())
    ->everyThirtyMinutes()
    ->name('travel.update-booking-status')
    ->withoutOverlapping(25);


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
use App\Domains\Channels\Jobs\ArchiveInactiveChannelsJob;
use App\Domains\Channels\Jobs\PostSchedulerJob;
use App\Domains\Channels\Jobs\SubscriptionRenewalJob;
use App\Domains\Courses\Jobs\CertificateGenerationJob;
use App\Domains\Courses\Jobs\EnrollmentReminderJob;
use App\Domains\Entertainment\Jobs\CalculateEntertainerEarningsJob;
use App\Domains\Entertainment\Jobs\SendEventReminderJob;
use App\Domains\Fashion\Jobs\CalculateStoreEarningsJob;
use App\Domains\Fashion\Jobs\UpdateOrderStatusJob;
use App\Domains\Fitness\Jobs\CalculateTrainerEarningsJob;
use App\Domains\Fitness\Jobs\SendClassReminderJob;
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

// Агрегация ежедневной аналитики — 01:30 UTC (dispatches per-tenant via command)
Schedule::call(function () {
    \App\Models\Tenant::query()->pluck('id')->each(function (int $tenantId) {
        AggregateDailyAnalyticsJob::dispatch($tenantId);
    });
})->dailyAt('01:30')->name('platform.aggregate-daily-analytics');

// Пересчёт аналитики — каждый час (dispatches per-tenant)
Schedule::call(function () {
    \App\Models\Tenant::query()->pluck('id')->each(function (int $tenantId) {
        RecalculateAnalyticsJob::dispatch($tenantId);
    });
})->hourly()->name('platform.recalculate-analytics');

// ML-фрод: переобучение модели — 02:00 UTC
Schedule::call(fn () => FraudMLRecalculationJob::dispatch())
    ->dailyAt('02:00')
    ->name('platform.fraud-ml-recalculation');

// Прогноз спроса — 04:30 UTC (после переобучения фрода)
Schedule::job(new DemandForecastJob())
    ->dailyAt('04:30')
    ->name('platform.demand-forecast')
    ->withoutOverlapping(120);

// Качество рекомендаций — 05:00 UTC
Schedule::call(fn () => RecommendationQualityJob::dispatch())
    ->dailyAt('05:00')
    ->name('platform.recommendation-quality');

// Уведомления о низком остатке — 08:00 UTC
Schedule::job(new LowStockNotificationJob())
    ->dailyAt('08:00')
    ->name('platform.low-stock-notification')
    ->withoutOverlapping(60);

// Обработка выплат бизнесам — 10:00 UTC
Schedule::call(fn () => PayoutProcessingJob::dispatch())
    ->dailyAt('10:00')
    ->name('platform.payout-processing');

// Начисление бонусов за оборот — 1-е число месяца 07:00 UTC
Schedule::call(fn () => BonusAccrualJob::dispatch())
    ->monthlyOn(1, '07:00')
    ->name('platform.bonus-accrual');

// Очистка истёкших бонусов — каждое воскресенье 04:00 UTC
Schedule::job(new CleanupExpiredBonusesJob())
    ->weeklyOn(0, '04:00')
    ->name('platform.cleanup-expired-bonuses')
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
Schedule::job(new AutoCloseOrderJob())
    ->everyFifteenMinutes()
    ->name('food.auto-close-order')
    ->withoutOverlapping(10);

Schedule::job(new OrderReadyReminderJob())
    ->everyFifteenMinutes()
    ->name('food.order-ready-reminder')
    ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// FREELANCE
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateFreelancerEarningsJob())
    ->dailyAt('04:30')
    ->name('freelance.calculate-earnings')
    ->withoutOverlapping(60);

Schedule::job(new UpdateDeliverableStatusJob())
    ->hourly()
    ->name('freelance.update-deliverable-status')
    ->withoutOverlapping(55);

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
Schedule::job(new AutoCheckOutJob())
    ->hourly()
    ->name('hotels.auto-checkout')
    ->withoutOverlapping(55);

Schedule::job(new CheckInReminderJob())
    ->dailyAt('09:00')
    ->name('hotels.check-in-reminder')
    ->withoutOverlapping(30);

// ══════════════════════════════════════════════════════════════════════════════
// LOGISTICS
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateCourierEarningsJob())
    ->dailyAt('05:30')
    ->name('logistics.calculate-earnings')
    ->withoutOverlapping(60);

Schedule::job(new UpdateShipmentStatusJob())
    ->everyFifteenMinutes()
    ->name('logistics.update-shipment-status')
    ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// MEDICAL
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateClinicEarningsJob())
    ->dailyAt('06:00')
    ->name('medical.calculate-earnings')
    ->withoutOverlapping(60);

Schedule::job(new UpdateAppointmentStatusJob())
    ->everyThirtyMinutes()
    ->name('medical.update-appointment-status')
    ->withoutOverlapping(25);

// ══════════════════════════════════════════════════════════════════════════════
// PET
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new PetCalculateClinicEarningsJob())
    ->dailyAt('06:30')
    ->name('pet.calculate-earnings')
    ->withoutOverlapping(60);

Schedule::job(new PetUpdateAppointmentStatusJob())
    ->everyThirtyMinutes()
    ->name('pet.update-appointment-status')
    ->withoutOverlapping(25);

// ══════════════════════════════════════════════════════════════════════════════
// PHOTOGRAPHY
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateRatingsJob())
    ->dailyAt('07:00')
    ->name('photography.calculate-ratings')
    ->withoutOverlapping(60);

Schedule::job(new UpdateSessionStatusJob())
    ->hourly()
    ->name('photography.update-session-status')
    ->withoutOverlapping(55);

// ══════════════════════════════════════════════════════════════════════════════
// REAL ESTATE
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new PropertyAutoCloseJob())
    ->dailyAt('07:30')
    ->name('realestate.property-auto-close')
    ->withoutOverlapping(60);

Schedule::job(new ViewingReminderJob())
    ->hourly()
    ->name('realestate.viewing-reminder')
    ->withoutOverlapping(55);

// ══════════════════════════════════════════════════════════════════════════════
// SPORTS
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new BookingConfirmationJob())
    ->everyThirtyMinutes()
    ->name('sports.booking-confirmation')
    ->withoutOverlapping(25);

Schedule::job(new ClassReminderJob())
    ->everyThirtyMinutes()
    ->name('sports.class-reminder')
    ->withoutOverlapping(25);

// ══════════════════════════════════════════════════════════════════════════════
// TICKETS
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new EventReminderJob())
    ->hourly()
    ->name('tickets.event-reminder')
    ->withoutOverlapping(55);

Schedule::job(new TicketGenerationJob())
    ->everyFifteenMinutes()
    ->name('tickets.ticket-generation')
    ->withoutOverlapping(10);

// ══════════════════════════════════════════════════════════════════════════════
// TRAVEL
// ══════════════════════════════════════════════════════════════════════════════
Schedule::job(new CalculateAgencyEarningsJob())
    ->dailyAt('08:00')
    ->name('travel.calculate-earnings')
    ->withoutOverlapping(60);

Schedule::job(new UpdateBookingStatusJob())
    ->everyThirtyMinutes()
    ->name('travel.update-booking-status')
    ->withoutOverlapping(25);


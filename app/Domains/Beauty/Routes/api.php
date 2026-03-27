<?php declare(strict_types=1);

use App\Modules\Beauty\Http\Controllers\BeautyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Салоны
    Route::get('/api/beauty/salons', [BeautyController::class, 'index'])->name('beauty.salons.index');
    Route::post('/api/beauty/salons', [BeautyController::class, 'store'])->name('beauty.salons.store');
    Route::get('/api/beauty/salons/{id}', [BeautyController::class, 'show'])->name('beauty.salons.show');
    Route::put('/api/beauty/salons/{id}', [BeautyController::class, 'update'])->name('beauty.salons.update');
    Route::delete('/api/beauty/salons/{id}', [BeautyController::class, 'destroy'])->name('beauty.salons.destroy');

    // Мастера
    Route::get('/api/beauty/masters', [BeautyController::class, 'indexMasters'])->name('beauty.masters.index');
    Route::post('/api/beauty/masters', [BeautyController::class, 'storeMaster'])->name('beauty.masters.store');
    Route::get('/api/beauty/masters/{id}', [BeautyController::class, 'showMaster'])->name('beauty.masters.show');

    // Услуги
    Route::get('/api/beauty/services', [BeautyController::class, 'indexServices'])->name('beauty.services.index');
    Route::post('/api/beauty/services', [BeautyController::class, 'storeService'])->name('beauty.services.store');
    Route::get('/api/beauty/services/{id}', [BeautyController::class, 'showService'])->name('beauty.services.show');

    // Записи
    Route::get('/api/beauty/appointments', [BeautyController::class, 'indexAppointments'])->name('beauty.appointments.index');
    Route::post('/api/beauty/appointments', [BeautyController::class, 'storeAppointment'])->name('beauty.appointments.store');
    Route::get('/api/beauty/appointments/{id}', [BeautyController::class, 'showAppointment'])->name('beauty.appointments.show');
    Route::post('/api/beauty/appointments/{id}/cancel', [BeautyController::class, 'cancelAppointment'])->name('beauty.appointments.cancel');
    Route::post('/api/beauty/appointments/{id}/complete', [BeautyController::class, 'completeAppointment'])->name('beauty.appointments.complete');
    Route::get('/api/beauty/appointments/available-slots', [BeautyController::class, 'availableSlots'])->name('beauty.appointments.available-slots');

    // Товары
    Route::get('/api/beauty/products', [BeautyController::class, 'indexProducts'])->name('beauty.products.index');
    Route::post('/api/beauty/products', [BeautyController::class, 'storeProduct'])->name('beauty.products.store');
    Route::get('/api/beauty/products/{id}', [BeautyController::class, 'showProduct'])->name('beauty.products.show');

    // Портфолио
    Route::get('/api/beauty/portfolio', [BeautyController::class, 'indexPortfolio'])->name('beauty.portfolio.index');
    Route::post('/api/beauty/portfolio', [BeautyController::class, 'storePortfolioItem'])->name('beauty.portfolio.store');
    Route::delete('/api/beauty/portfolio/{id}', [BeautyController::class, 'destroyPortfolioItem'])->name('beauty.portfolio.destroy');

    // Отзывы
    Route::get('/api/beauty/reviews', [BeautyController::class, 'indexReviews'])->name('beauty.reviews.index');
    Route::post('/api/beauty/reviews', [BeautyController::class, 'storeReview'])->name('beauty.reviews.store');
    Route::delete('/api/beauty/reviews/{id}', [BeautyController::class, 'destroyReview'])->name('beauty.reviews.destroy');
});

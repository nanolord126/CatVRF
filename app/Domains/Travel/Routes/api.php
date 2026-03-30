<?php declare(strict_types=1);

namespace App\Domains\Travel\Routes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class api extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Бронирования (Слой 8)
        Route::prefix('bookings')->group(function () {
            Route::post('/', [BookingApiController::class, 'create'])->name('travel.bookings.create');
            Route::post('/{id}/pay', [BookingApiController::class, 'pay'])->name('travel.bookings.pay');
            Route::delete('/{id}', [BookingApiController::class, 'cancel'])->name('travel.bookings.cancel');
            Route::get('/{id}', [BookingApiController::class, 'show'])->name('travel.bookings.show');
        });

        // AI Планировщик (Слой 5 + 8)
        Route::prefix('ai-planner')->group(function () {
            Route::post('/generate', [TripPlannerApiController::class, 'generate'])->name('travel.ai.generate');
            Route::get('/history', [TripPlannerApiController::class, 'history'])->name('travel.ai.history');
        });

        // Витрина (Слой 8)
        Route::get('/tours', [\App\Domains\Travel\Controllers\Api\TourApiController::class, 'index'])->name('travel.tours.index');
        Route::get('/tours/{id}', [\App\Domains\Travel\Controllers\Api\TourApiController::class, 'show'])->name('travel.tours.show');
}

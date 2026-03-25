<?php declare(strict_types=1);

use App\Modules\Beauty\Http\Controllers\BeautyController;
use Illuminate\Support\Facades\Route;

$this->route->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Салоны
    $this->route->get('/api/beauty/salons', [BeautyController::class, 'index'])->name('beauty.salons.index');
    $this->route->post('/api/beauty/salons', [BeautyController::class, 'store'])->name('beauty.salons.store');
    $this->route->get('/api/beauty/salons/{id}', [BeautyController::class, 'show'])->name('beauty.salons.show');
    $this->route->put('/api/beauty/salons/{id}', [BeautyController::class, 'update'])->name('beauty.salons.update');
    $this->route->delete('/api/beauty/salons/{id}', [BeautyController::class, 'destroy'])->name('beauty.salons.destroy');

    // Мастера
    $this->route->get('/api/beauty/masters', [BeautyController::class, 'indexMasters'])->name('beauty.masters.index');
    $this->route->post('/api/beauty/masters', [BeautyController::class, 'storeMaster'])->name('beauty.masters.store');
    $this->route->get('/api/beauty/masters/{id}', [BeautyController::class, 'showMaster'])->name('beauty.masters.show');

    // Услуги
    $this->route->get('/api/beauty/services', [BeautyController::class, 'indexServices'])->name('beauty.services.index');
    $this->route->post('/api/beauty/services', [BeautyController::class, 'storeService'])->name('beauty.services.store');
    $this->route->get('/api/beauty/services/{id}', [BeautyController::class, 'showService'])->name('beauty.services.show');

    // Записи
    $this->route->get('/api/beauty/appointments', [BeautyController::class, 'indexAppointments'])->name('beauty.appointments.index');
    $this->route->post('/api/beauty/appointments', [BeautyController::class, 'storeAppointment'])->name('beauty.appointments.store');
    $this->route->get('/api/beauty/appointments/{id}', [BeautyController::class, 'showAppointment'])->name('beauty.appointments.show');
    $this->route->post('/api/beauty/appointments/{id}/cancel', [BeautyController::class, 'cancelAppointment'])->name('beauty.appointments.cancel');
    $this->route->post('/api/beauty/appointments/{id}/complete', [BeautyController::class, 'completeAppointment'])->name('beauty.appointments.complete');
    $this->route->get('/api/beauty/appointments/available-slots', [BeautyController::class, 'availableSlots'])->name('beauty.appointments.available-slots');

    // Товары
    $this->route->get('/api/beauty/products', [BeautyController::class, 'indexProducts'])->name('beauty.products.index');
    $this->route->post('/api/beauty/products', [BeautyController::class, 'storeProduct'])->name('beauty.products.store');
    $this->route->get('/api/beauty/products/{id}', [BeautyController::class, 'showProduct'])->name('beauty.products.show');

    // Портфолио
    $this->route->get('/api/beauty/portfolio', [BeautyController::class, 'indexPortfolio'])->name('beauty.portfolio.index');
    $this->route->post('/api/beauty/portfolio', [BeautyController::class, 'storePortfolioItem'])->name('beauty.portfolio.store');
    $this->route->delete('/api/beauty/portfolio/{id}', [BeautyController::class, 'destroyPortfolioItem'])->name('beauty.portfolio.destroy');

    // Отзывы
    $this->route->get('/api/beauty/reviews', [BeautyController::class, 'indexReviews'])->name('beauty.reviews.index');
    $this->route->post('/api/beauty/reviews', [BeautyController::class, 'storeReview'])->name('beauty.reviews.store');
    $this->route->delete('/api/beauty/reviews/{id}', [BeautyController::class, 'destroyReview'])->name('beauty.reviews.destroy');
});

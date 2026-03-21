<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Photography\Http\Controllers\PhotoStudioController;
use App\Domains\Photography\Http\Controllers\PhotoSessionController;
use App\Domains\Photography\Http\Controllers\PhotoGalleryController;
use App\Domains\Photography\Http\Controllers\PhotoReviewController;
use App\Domains\Photography\Http\Controllers\B2BPhotoController;

// Публичные маршруты (B2C)
Route::prefix('api/v1/photography')->group(function () {
	// Фотостудии (поиск, список, просмотр)
	Route::get('/studios', [PhotoStudioController::class, 'index']);
	Route::get('/studios/{id}', [PhotoStudioController::class, 'show']);
	Route::get('/studios/{id}/packages', [PhotoStudioController::class, 'packages']);
	Route::get('/studios/{id}/photographers', [PhotoStudioController::class, 'photographers']);
	Route::get('/studios/{id}/reviews', [PhotoStudioController::class, 'reviews']);
	Route::get('/studios/search', [PhotoStudioController::class, 'search']);

	// Фотографы (профиль, портфолио)
	Route::get('/photographers/{id}', [PhotoGalleryController::class, 'show']);
	Route::get('/photographers/{id}/portfolio', [PhotoGalleryController::class, 'portfolio']);
	Route::get('/photographers/{id}/galleries', [PhotoGalleryController::class, 'galleries']);

	// Галереи (публичные)
	Route::get('/galleries/{id}', [PhotoGalleryController::class, 'showGallery']);
	Route::get('/galleries/{id}/photos', [PhotoGalleryController::class, 'photos']);

	// Отзывы (читать)
	Route::get('/reviews', [PhotoReviewController::class, 'index']);
	Route::get('/reviews/{id}', [PhotoReviewController::class, 'show']);
	Route::get('/photographers/{id}/reviews', [PhotoReviewController::class, 'photographerReviews']);
	Route::get('/studios/{id}/reviews', [PhotoReviewController::class, 'studioReviews']);

	// Аутентифицированные маршруты (B2C - пользователи)
	Route::middleware(['auth:api'])->group(function () {
		// Бронирование сессий
		Route::post('/sessions', [PhotoSessionController::class, 'store']);
		Route::get('/my-sessions', [PhotoSessionController::class, 'mySessions']);
		Route::get('/sessions/{id}', [PhotoSessionController::class, 'show']);
		Route::patch('/sessions/{id}/status', [PhotoSessionController::class, 'updateStatus']);
		Route::delete('/sessions/{id}', [PhotoSessionController::class, 'cancel']);

		// Отзывы (создание, обновление)
		Route::post('/sessions/{id}/reviews', [PhotoReviewController::class, 'store']);
		Route::patch('/reviews/{id}', [PhotoReviewController::class, 'update']);
		Route::delete('/reviews/{id}', [PhotoReviewController::class, 'destroy']);
		Route::post('/reviews/{id}/helpful', [PhotoReviewController::class, 'markHelpful']);

		// Галереи фотографов (если пользователь - фотограф)
		Route::post('/galleries', [PhotoGalleryController::class, 'store']);
		Route::patch('/galleries/{id}', [PhotoGalleryController::class, 'update']);
		Route::delete('/galleries/{id}', [PhotoGalleryController::class, 'destroy']);
		Route::post('/galleries/{id}/photos', [PhotoGalleryController::class, 'addPhotos']);
	});

	// B2B маршруты (корпоративные съемки)
	Route::prefix('b2b')->middleware(['auth:api'])->group(function () {
		// Витрины компаний
		Route::get('/storefronts', [B2BPhotoController::class, 'storefronts']);
		Route::post('/storefronts', [B2BPhotoController::class, 'createStorefront']);
		Route::get('/storefronts/{id}', [B2BPhotoController::class, 'showStorefront']);
		Route::patch('/storefronts/{id}', [B2BPhotoController::class, 'updateStorefront']);

		// B2B заказы
		Route::post('/orders', [B2BPhotoController::class, 'createOrder']);
		Route::get('/orders', [B2BPhotoController::class, 'orders']);
		Route::get('/my-orders', [B2BPhotoController::class, 'myB2BOrders']);
		Route::get('/orders/{id}', [B2BPhotoController::class, 'showOrder']);
		Route::patch('/orders/{id}/status', [B2BPhotoController::class, 'updateOrderStatus']);
		Route::patch('/orders/{id}/approve', [B2BPhotoController::class, 'approveOrder']);
		Route::patch('/orders/{id}/reject', [B2BPhotoController::class, 'rejectOrder']);
	});

	// Admin маршруты
	Route::prefix('admin')->middleware(['auth:api', 'is_admin'])->group(function () {
		Route::get('/stats', [PhotoStudioController::class, 'stats']);
		Route::get('/top-studios', [PhotoStudioController::class, 'topStudios']);
		Route::get('/top-photographers', [PhotoGalleryController::class, 'topPhotographers']);
		Route::get('/sessions/pending', [PhotoSessionController::class, 'pendingSessions']);
		Route::get('/b2b-orders/pending', [B2BPhotoController::class, 'pendingB2BOrders']);
		Route::patch('/studios/{id}/verify', [PhotoStudioController::class, 'verify']);
		Route::patch('/b2b-storefronts/{id}/verify-inn', [B2BPhotoController::class, 'verifyInn']);
	});
});

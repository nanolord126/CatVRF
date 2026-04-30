<?php

declare(strict_types=1);

use App\Http\Controllers\Supermarket\SupermarketCheckoutController;
use App\Http\Controllers\Supermarket\SupermarketDeliveryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supermarket B2C API Routes
|--------------------------------------------------------------------------
|
| Публичное API вертикали Supermarket.
|
| Middleware pipeline:
|   correlation-id → tenant → rate-limit → controller
|   (auth:sanctum для авторизованных эндпоинтов)
|
| Prefix: /api/supermarket
|
*/

Route::prefix('api/supermarket')
    ->middleware(['correlation-id', 'tenant', 'throttle:120,1'])
    ->group(function (): void {

        /*
        |--------------------------------------------------------------
        | Публичные (без авторизации) — каталог и доставка
        |--------------------------------------------------------------
        */

        // Проверка доступности доставки по адресу
        Route::post('delivery/check-availability', [SupermarketDeliveryController::class, 'checkAvailability'])
            ->name('supermarket.delivery.check-availability');

        // Получить доступные слоты доставки
        Route::post('delivery/slots', [SupermarketDeliveryController::class, 'getAvailableSlots'])
            ->name('supermarket.delivery.slots');

        // Расчёт стоимости доставки
        Route::post('delivery/calculate', [SupermarketDeliveryController::class, 'calculateDelivery'])
            ->name('supermarket.delivery.calculate');

        // Популярные товары (кэшированные)
        Route::get('products/popular', [SupermarketCheckoutController::class, 'getPopularProducts'])
            ->name('supermarket.products.popular');

        // Категории товаров (кэшированные)
        Route::get('categories', [SupermarketCheckoutController::class, 'getCategories'])
            ->name('supermarket.categories');

        /*
        |--------------------------------------------------------------
        | Авторизованные (auth:sanctum) — чекаут и заказы
        |--------------------------------------------------------------
        */

        Route::middleware(['auth:sanctum', 'fraud-check'])
            ->group(function (): void {

                // --- Чекаут ---
                Route::post('checkout/prepare', [SupermarketCheckoutController::class, 'prepareCheckout'])
                    ->name('supermarket.checkout.prepare')
                    ->middleware('throttle:60,1'); // max 60 запросов в минуту

                Route::post('checkout/create', [SupermarketCheckoutController::class, 'createOrder'])
                    ->name('supermarket.checkout.create')
                    ->middleware('throttle:30,1'); // max 30 заказов в минуту

                // --- Заказы ---
                Route::get('orders', [SupermarketCheckoutController::class, 'index'])
                    ->name('supermarket.orders.index');

                Route::get('orders/{order}', [SupermarketCheckoutController::class, 'show'])
                    ->name('supermarket.orders.show')
                    ->whereNumber('order');

                Route::post('orders/{order}/cancel', [SupermarketCheckoutController::class, 'cancelOrder'])
                    ->name('supermarket.orders.cancel')
                    ->whereNumber('order');

                Route::post('orders/{order}/confirm-payment', [SupermarketCheckoutController::class, 'confirmPayment'])
                    ->name('supermarket.orders.confirm-payment')
                    ->whereNumber('order');

                // --- Резервирование ---
                Route::post('reservations/create', [SupermarketCheckoutController::class, 'createReservation'])
                    ->name('supermarket.reservations.create')
                    ->middleware('throttle:60,1'); // max 60 резервов в минуту

                Route::post('reservations/{reservation}/extend', [SupermarketCheckoutController::class, 'extendReservation'])
                    ->name('supermarket.reservations.extend')
                    ->whereNumber('reservation');

                Route::post('reservations/{reservation}/release', [SupermarketCheckoutController::class, 'releaseReservation'])
                    ->name('supermarket.reservations.release')
                    ->whereNumber('reservation');

                // --- Холодовая цепь ---
                Route::get('orders/{order}/cold-chain-status', [SupermarketCheckoutController::class, 'getColdChainStatus'])
                    ->name('supermarket.orders.cold-chain-status')
                    ->whereNumber('order');

                // --- Трекинг ---
                Route::get('orders/{order}/tracking', [SupermarketCheckoutController::class, 'getTracking'])
                    ->name('supermarket.orders.tracking')
                    ->whereNumber('order');
            });

        /*
        |--------------------------------------------------------------
        | B2B (для бизнес-покупателей)
        |--------------------------------------------------------------
        */

        Route::prefix('b2b')
            ->middleware(['auth:sanctum', 'b2b.verified', 'fraud-check'])
            ->group(function (): void {
                // --- Каталог с оптовыми ценами ---
                Route::get('catalog', [\App\Http\Controllers\Supermarket\B2BCatalogController::class, 'index'])
                    ->name('supermarket.b2b.catalog');

                Route::get('catalog/{product}', [\App\Http\Controllers\Supermarket\B2BCatalogController::class, 'show'])
                    ->name('supermarket.b2b.catalog.show')
                    ->whereNumber('product');

                // --- Корзина ---
                Route::post('cart/add', [\App\Http\Controllers\Supermarket\B2BCartController::class, 'add'])
                    ->name('supermarket.b2b.cart.add')
                    ->middleware('throttle:60,1');

                Route::get('cart', [\App\Http\Controllers\Supermarket\B2BCartController::class, 'index'])
                    ->name('supermarket.b2b.cart.index');

                Route::delete('cart/{item}', [\App\Http\Controllers\Supermarket\B2BCartController::class, 'remove'])
                    ->name('supermarket.b2b.cart.remove')
                    ->whereNumber('item');

                // --- Чекаут и заказы ---
                Route::post('checkout', [\App\Http\Controllers\Supermarket\B2BCheckoutController::class, 'checkout'])
                    ->name('supermarket.b2b.checkout')
                    ->middleware('throttle:30,1');

                Route::get('orders', [\App\Http\Controllers\Supermarket\B2BOrderController::class, 'index'])
                    ->name('supermarket.b2b.orders.index');

                Route::get('orders/{order}', [\App\Http\Controllers\Supermarket\B2BOrderController::class, 'show'])
                    ->name('supermarket.b2b.orders.show')
                    ->whereNumber('order');

                Route::post('orders/{order}/cancel', [\App\Http\Controllers\Supermarket\B2BOrderController::class, 'cancel'])
                    ->name('supermarket.b2b.orders.cancel')
                    ->whereNumber('order');

                // --- Документы ---
                Route::get('documents/{order}', [\App\Http\Controllers\Supermarket\B2BDocumentController::class, 'download'])
                    ->name('supermarket.b2b.documents.download')
                    ->whereNumber('order');

                Route::get('documents/{order}/invoice', [\App\Http\Controllers\Supermarket\B2BDocumentController::class, 'downloadInvoice'])
                    ->name('supermarket.b2b.documents.invoice')
                    ->whereNumber('order');

                Route::get('documents/{order}/upd', [\App\Http\Controllers\Supermarket\B2BDocumentController::class, 'downloadUPD'])
                    ->name('supermarket.b2b.documents.upd')
                    ->whereNumber('order');

        /*
        |--------------------------------------------------------------
        | Supplier Document Management (для поставщиков)
        |--------------------------------------------------------------
        */

        Route::middleware(['auth:sanctum', 'supplier.verified', 'fraud-check'])
            ->prefix('supplier/documents')
            ->group(function (): void {
                // CRUD для документов
                Route::get('/', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'index'])
                    ->name('supermarket.supplier.documents.index');

                Route::post('/', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'store'])
                    ->name('supermarket.supplier.documents.store')
                    ->middleware('throttle:30,1');

                Route::get('{document}', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'show'])
                    ->name('supermarket.supplier.documents.show')
                    ->whereNumber('document');

                Route::put('{document}', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'update'])
                    ->name('supermarket.supplier.documents.update')
                    ->whereNumber('document');

                Route::delete('{document}', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'destroy'])
                    ->name('supermarket.supplier.documents.destroy')
                    ->whereNumber('document');

                // B2B Certificate operations
                Route::post('{document}/add-quantity', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'addQuantity'])
                    ->name('supermarket.supplier.documents.add-quantity')
                    ->whereNumber('document');

                Route::post('{document}/close', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'closeCertificate'])
                    ->name('supermarket.supplier.documents.close')
                    ->whereNumber('document');

                Route::post('{document}/reopen', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'reopenCertificate'])
                    ->name('supermarket.supplier.documents.reopen')
                    ->whereNumber('document');

                Route::post('{document}/validate', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'validateCertificate'])
                    ->name('supermarket.supplier.documents.validate')
                    ->whereNumber('document');

                // History
                Route::get('{document}/history', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'history'])
                    ->name('supermarket.supplier.documents.history')
                    ->whereNumber('document');

                // Distribution
                Route::post('{document}/distribute', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'distribute'])
                    ->name('supermarket.supplier.documents.distribute')
                    ->whereNumber('document');

                Route::post('{document}/distribute/{client}', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'distributeToClient'])
                    ->name('supermarket.supplier.documents.distribute-to-client')
                    ->whereNumber('document')
                    ->whereNumber('client');

                // Stats
                Route::get('stats', [\Modules\Supermarket\Infrastructure\Http\Controllers\DocumentController::class, 'stats'])
                    ->name('supermarket.supplier.documents.stats');
            });

        /*
        |--------------------------------------------------------------
        | B2B Company Registration (для регистрации компании)
        |--------------------------------------------------------------
        */

        Route::middleware(['auth:sanctum'])
            ->prefix('b2b/company')
            ->group(function (): void {
                Route::post('register', [\App\Http\Controllers\Supermarket\B2BCompanyController::class, 'register'])
                    ->name('supermarket.b2b.company.register')
                    ->middleware('throttle:5,1'); // max 5 регистраций в час

                Route::get('status', [\App\Http\Controllers\Supermarket\B2BCompanyController::class, 'status'])
                    ->name('supermarket.b2b.company.status');
            });

        /*
        |--------------------------------------------------------------
        | Admin/Internal (для внутреннего использования)
        |--------------------------------------------------------------
        */

        Route::middleware(['auth:sanctum', 'can:admin'])
            ->prefix('admin')
            ->group(function (): void {
                // Освобождение истекших резервов (для планировщика)
                Route::post('reservations/release-expired', [SupermarketCheckoutController::class, 'releaseExpiredReservations'])
                    ->name('supermarket.admin.reservations.release-expired');

                // Статистика по резервам
                Route::get('reservations/stats', [SupermarketCheckoutController::class, 'getReservationStats'])
                    ->name('supermarket.admin.reservations.stats');

                // B2B: Модерация компаний
                Route::post('b2b/companies/{company}/approve', [\App\Http\Controllers\Supermarket\B2BAdminController::class, 'approveCompany'])
                    ->name('supermarket.admin.b2b.companies.approve')
                    ->whereNumber('company');

                Route::post('b2b/companies/{company}/reject', [\App\Http\Controllers\Supermarket\B2BAdminController::class, 'rejectCompany'])
                    ->name('supermarket.admin.b2b.companies.reject')
                    ->whereNumber('company');
            });
    });

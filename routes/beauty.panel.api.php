<?php declare(strict_types=1);

/**
 * Beauty Panel API Routes — маршруты для B2B-панели Beauty.
 *
 * Все эндпоинты, которые используют 28 Vue-компонентов Beauty.
 * Middleware: auth:sanctum, tenant, correlation-id, fraud-check.
 *
 * Prefix: /api/v1/beauty
 */

use App\Domains\Beauty\Http\Controllers\BeautyPanelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'tenant'])->prefix('api/v1/beauty')->group(function () {
    /* ── Dashboard ── */
    Route::get('dashboard', [BeautyPanelController::class, 'dashboard'])
        ->name('beauty.panel.dashboard');

    /* ── Analytics & Finance ── */
    Route::get('analytics', [BeautyPanelController::class, 'analytics'])
        ->name('beauty.panel.analytics');
    Route::get('analytics/finances', [BeautyPanelController::class, 'financeStats'])
        ->name('beauty.panel.finances');
    Route::get('reports/{type}', [BeautyPanelController::class, 'reportData'])
        ->name('beauty.panel.reports');

    /* ── Staff / HR ── */
    Route::get('staff', [BeautyPanelController::class, 'staffIndex'])
        ->name('beauty.panel.staff.index');
    Route::post('staff', [BeautyPanelController::class, 'staffStore'])
        ->name('beauty.panel.staff.store');
    Route::put('staff/{id}', [BeautyPanelController::class, 'staffUpdate'])
        ->name('beauty.panel.staff.update');
    Route::post('staff/{id}/payout', [BeautyPanelController::class, 'staffPayout'])
        ->name('beauty.panel.staff.payout');

    /* ── Loyalty ── */
    Route::get('loyalty', [BeautyPanelController::class, 'loyaltyIndex'])
        ->name('beauty.panel.loyalty.index');
    Route::put('loyalty', [BeautyPanelController::class, 'loyaltyUpdate'])
        ->name('beauty.panel.loyalty.update');
    Route::post('loyalty/award', [BeautyPanelController::class, 'loyaltyAward'])
        ->name('beauty.panel.loyalty.award');
    Route::post('loyalty/deduct', [BeautyPanelController::class, 'loyaltyDeduct'])
        ->name('beauty.panel.loyalty.deduct');

    /* ── Notifications ── */
    Route::post('notifications/send', [BeautyPanelController::class, 'notificationSend'])
        ->name('beauty.panel.notifications.send');
    Route::post('notifications/bulk', [BeautyPanelController::class, 'notificationBulk'])
        ->name('beauty.panel.notifications.bulk');
    Route::get('notifications/templates', [BeautyPanelController::class, 'notificationTemplates'])
        ->name('beauty.panel.notifications.templates');
    Route::post('notifications/templates', [BeautyPanelController::class, 'notificationTemplateStore'])
        ->name('beauty.panel.notifications.templates.store');

    /* ── Chat ── */
    Route::get('chats', [BeautyPanelController::class, 'chatIndex'])
        ->name('beauty.panel.chats.index');
    Route::get('chats/{chatId}/messages', [BeautyPanelController::class, 'chatMessages'])
        ->name('beauty.panel.chats.messages');
    Route::post('chats/{chatId}/messages', [BeautyPanelController::class, 'chatSendMessage'])
        ->name('beauty.panel.chats.send');

    /* ── CRM / Clients ── */
    Route::get('clients', [BeautyPanelController::class, 'clientsIndex'])
        ->name('beauty.panel.clients.index');
    Route::get('clients/segments', [BeautyPanelController::class, 'clientSegments'])
        ->name('beauty.panel.clients.segments');
    Route::get('clients/{id}', [BeautyPanelController::class, 'clientShow'])
        ->name('beauty.panel.clients.show');

    /* ── Public Pages ── */
    Route::get('pages', [BeautyPanelController::class, 'pagesIndex'])
        ->name('beauty.panel.pages.index');
    Route::post('pages', [BeautyPanelController::class, 'pageStore'])
        ->name('beauty.panel.pages.store');
    Route::put('pages/{id}', [BeautyPanelController::class, 'pageUpdate'])
        ->name('beauty.panel.pages.update');
    Route::delete('pages/{id}', [BeautyPanelController::class, 'pageDestroy'])
        ->name('beauty.panel.pages.destroy');
    Route::get('pages/stats', [BeautyPanelController::class, 'pageStats'])
        ->name('beauty.panel.pages.stats');

    /* ── Promos ── */
    Route::get('promos', [BeautyPanelController::class, 'promosIndex'])
        ->name('beauty.panel.promos.index');
    Route::post('promos', [BeautyPanelController::class, 'promoStore'])
        ->name('beauty.panel.promos.store');
    Route::put('promos/{id}', [BeautyPanelController::class, 'promoUpdate'])
        ->name('beauty.panel.promos.update');
    Route::delete('promos/{id}', [BeautyPanelController::class, 'promoDestroy'])
        ->name('beauty.panel.promos.destroy');

    /* ── Export ── */
    Route::get('export/{type}', [BeautyPanelController::class, 'exportData'])
        ->name('beauty.panel.export');

    /* ── AI Try-On ── */
    Route::post('ai/try-on', [BeautyPanelController::class, 'aiTryOn'])
        ->name('beauty.panel.ai.tryon');
});

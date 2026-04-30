<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Audit\Controllers\AuditApiController;
use App\Domains\Audit\Controllers\AuditController;

/*
|--------------------------------------------------------------------------
| Audit API Routes
|--------------------------------------------------------------------------
|
| Internal API endpoints for audit log access and management.
| These routes are protected and meant for internal use.
|
*/

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    // API endpoints for programmatic access
    Route::prefix('api/v1/audit')->group(function () {
        Route::get('/logs', [AuditApiController::class, 'index'])
            ->name('audit.api.index')
            ->middleware('can:view_any_audit_logs');

        Route::get('/logs/{id}', [AuditApiController::class, 'show'])
            ->name('audit.api.show')
            ->middleware('can:view_audit_logs');

        Route::get('/logs/subject/{type}/{id?}', [AuditApiController::class, 'bySubject'])
            ->name('audit.api.by_subject')
            ->middleware('can:view_audit_logs');

        Route::get('/logs/correlation/{correlationId}', [AuditApiController::class, 'byCorrelationId'])
            ->name('audit.api.by_correlation')
            ->middleware('can:view_audit_logs');

        Route::get('/logs/user/{userId}', [AuditApiController::class, 'byUser'])
            ->name('audit.api.by_user')
            ->middleware('can:view_audit_logs');

        Route::delete('/logs/user/{userId}', [AuditApiController::class, 'deleteByUser'])
            ->name('audit.api.delete_by_user')
            ->middleware('can:delete_audit_logs');

        Route::delete('/logs/subject/{type}/{id?}', [AuditApiController::class, 'deleteBySubject'])
            ->name('audit.api.delete_by_subject')
            ->middleware('can:delete_audit_logs');

        Route::post('/logs/prune', [AuditApiController::class, 'prune'])
            ->name('audit.api.prune')
            ->middleware('can:delete_audit_logs');
    });
});

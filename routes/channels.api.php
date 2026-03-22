<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Channels\ChannelController;
use App\Http\Controllers\Api\V1\Channels\PostController;
use App\Http\Controllers\Api\V1\Channels\ReactionController;
use App\Http\Controllers\Api\V1\Channels\SubscriberController;

/*
|--------------------------------------------------------------------------
| Channels API Routes
|--------------------------------------------------------------------------
| Подключается в routes/api.php через:
|   require __DIR__.'/channels.api.php';
|
| Все маршруты — /api/v1/... через общий prefix в api.php
*/

// ─── Публичные (без авторизации) ─────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Список тарифных планов
    Route::get('channels/plans', [ChannelController::class, 'plans'])
        ->name('api.channels.plans');

    // Публичная страница канала по slug
    Route::get('channels/{slug}', [ChannelController::class, 'publicShow'])
        ->name('api.channels.public-show');

    // Публичная лента постов канала
    Route::get('channels/{slug}/posts', [PostController::class, 'index'])
        ->name('api.channels.posts.index');

    // Просмотр поста (incrementViews внутри)
    Route::get('channels/{slug}/posts/{postUuid}', [PostController::class, 'show'])
        ->name('api.channels.posts.show');

    // Реакции поста — читать без авторизации
    Route::get('posts/{uuid}/react', [ReactionController::class, 'index'])
        ->name('api.posts.reactions.index');

    // Поставить / снять реакцию — без авторизации (sessionHash по cookie)
    Route::post('posts/{uuid}/react', [ReactionController::class, 'react'])
        ->name('api.posts.reactions.react');
});


// ─── Авторизованные ──────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'verified'])->group(function () {

    // -- Каналы ---
    Route::get('channels/my', [ChannelController::class, 'show'])
        ->name('api.channels.show');

    Route::post('channels', [ChannelController::class, 'store'])
        ->name('api.channels.store');

    Route::put('channels/{uuid}', [ChannelController::class, 'update'])
        ->name('api.channels.update');

    Route::post('channels/{uuid}/subscribe/{planSlug}', [ChannelController::class, 'subscribeToPlan'])
        ->name('api.channels.subscribe-plan');


    // -- Посты ---
    Route::post('channels/{uuid}/posts', [PostController::class, 'store'])
        ->name('api.channels.posts.store');

    Route::post('posts/{uuid}/publish', [PostController::class, 'publish'])
        ->name('api.posts.publish')
        ->middleware('can:publish,App\\Domains\\Channels\\Models\\Post');

    Route::post('posts/{uuid}/reject', [PostController::class, 'reject'])
        ->name('api.posts.reject')
        ->middleware('can:reject,App\\Domains\\Channels\\Models\\Post');

    Route::delete('posts/{uuid}', [PostController::class, 'destroy'])
        ->name('api.posts.destroy');

    // Персональная лента из всех подписок
    Route::get('feed/channels', [PostController::class, 'feed'])
        ->name('api.feed.channels');


    // -- Подписки пользователя на каналы ---
    Route::post('channels/{slug}/follow', [SubscriberController::class, 'subscribe'])
        ->name('api.channels.follow');

    Route::delete('channels/{slug}/follow', [SubscriberController::class, 'unsubscribe'])
        ->name('api.channels.unfollow');

    Route::get('channels/{slug}/follow', [SubscriberController::class, 'status'])
        ->name('api.channels.follow-status');

    Route::get('subscriptions/channels', [SubscriberController::class, 'mySubscriptions'])
        ->name('api.subscriptions.channels');
});

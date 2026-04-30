<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Restaurant\Presentation\Http\Livewire\KitchenDashboard;
use Modules\Restaurant\Presentation\Http\Livewire\StationView;

Route::middleware(['web', 'auth'])
    ->prefix('kds')
    ->group(function () {
        Route::get('/', KitchenDashboard::class)->name('kds.dashboard');
        Route::get('/station/{stationId}', StationView::class)->name('kds.station');
    });

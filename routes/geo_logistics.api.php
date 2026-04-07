<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\GeoLogistics\Presentation\Http\Controllers\CreateShipmentController;

Route::prefix('geo-logistics')->group(function () {
    Route::post('shipments', CreateShipmentController::class)->name('api.geo_logistics.shipments.store');
});


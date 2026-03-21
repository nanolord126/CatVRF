<?php declare(strict_types=1);

use App\Http\Controllers\Demo3DController;
use Illuminate\Support\Facades\Route;

// 3D Demo Routes (Public, no auth required for demo)
Route::get('/3d-demo', function () {
    return view('3d-demo-simple');
})->name('3d.demo.main');

Route::prefix('3d-demo')->controller(Demo3DController::class)->group(function () {
    Route::get('/', 'index')->name('3d.demo.index');
    Route::get('/product/{id}', 'product')->name('3d.demo.product');
});

// 3D System Health Check
Route::get('/3d-health', function () {
    return response()->json([
        'status' => 'ok',
        'system' => '3D Visualization System',
        'version' => '1.0 - Phase 1 Complete',
        'features' => [
            'product_3d' => true,
            'room_visualization' => true,
            'ar_support' => true,
            'mobile_ready' => true,
        ],
        'timestamp' => now(),
    ]);
})->name('3d.health');

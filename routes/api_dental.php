<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Dental\DentalApiController;

/*
| API Маршруты для вертикали DENTAL
*/

Route::prefix('v1/dental')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // Клиники и поиск
    Route::get('/clinics', [DentalApiController::class, 'getClinics']);
    Route::get('/clinics/{id}', [DentalApiController::class, 'getClinicDetails']);
    
    // Записи на прием
    Route::post('/appointments', [DentalApiController::class, 'createAppointment']);
    
    // AI Конструктор улыбки
    Route::post('/smile/analyze', [DentalApiController::class, 'analyzeSmile']);
    
    // Планы лечения
    Route::get('/treatment-plans', [DentalApiController::class, 'getUserTreatmentPlans']);
});

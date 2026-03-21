<?php declare(strict_types=1);

use App\Domains\Pet\Http\Controllers\PetAppointmentController;
use App\Domains\Pet\Http\Controllers\PetBoardingController;
use App\Domains\Pet\Http\Controllers\PetClinicController;
use App\Domains\Pet\Http\Controllers\PetProductController;
use App\Domains\Pet\Http\Controllers\PetReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('pet')->group(function () {
    // PUBLIC ROUTES
    Route::middleware('throttle:60,1')->group(function () {
        // Clinics
        Route::get('clinics', [PetClinicController::class, 'index']);
        Route::get('clinics/{id}', [PetClinicController::class, 'show']);
        Route::get('clinics/{id}/vets', [PetClinicController::class, 'getVets']);
        Route::get('clinics/{id}/services', [PetClinicController::class, 'getServices']);
        Route::get('clinics/{id}/reviews', [PetClinicController::class, 'getReviews']);

        // Vets
        Route::get('vets', [PetClinicController::class, 'getVetsList']);
        Route::get('vets/{id}', [PetClinicController::class, 'getVetDetail']);
        Route::get('vets/{id}/appointments', [PetClinicController::class, 'getVetAppointments']);
        Route::get('vets/{id/reviews', [PetClinicController::class, 'getVetReviews']);

        // Services
        Route::get('services', [PetClinicController::class, 'getServicesList']);
        Route::get('services/{id}', [PetClinicController::class, 'getServiceDetail']);

        // Products
        Route::get('products', [PetProductController::class, 'index']);
        Route::get('products/{id}', [PetProductController::class, 'show']);
        Route::get('products/search', [PetProductController::class, 'search']);

        // Reviews
        Route::get('reviews/clinic/{clinicId}', [PetReviewController::class, 'getClinicReviews']);
        Route::get('reviews/vet/{vetId}', [PetReviewController::class, 'getVetReviews']);

        // Search & Filter
        Route::get('search', [PetClinicController::class, 'search']);
        Route::get('filter', [PetClinicController::class, 'filter']);
    });

    // AUTHENTICATED ROUTES
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        // Appointments
        Route::post('appointments', [PetAppointmentController::class, 'store']);
        Route::get('appointments', [PetAppointmentController::class, 'index']);
        Route::get('appointments/{id}', [PetAppointmentController::class, 'show']);
        Route::put('appointments/{id}', [PetAppointmentController::class, 'update']);
        Route::delete('appointments/{id}', [PetAppointmentController::class, 'destroy']);
        Route::post('appointments/{id}/cancel', [PetAppointmentController::class, 'cancel']);

        // Boarding
        Route::post('boarding', [PetBoardingController::class, 'store']);
        Route::get('boarding', [PetBoardingController::class, 'index']);
        Route::get('boarding/{id}', [PetBoardingController::class, 'show']);
        Route::put('boarding/{id}', [PetBoardingController::class, 'update']);
        Route::delete('boarding/{id}', [PetBoardingController::class, 'destroy']);
        Route::post('boarding/{id}/cancel', [PetBoardingController::class, 'cancel']);

        // My Clinics
        Route::post('clinics', [PetClinicController::class, 'store']);
        Route::get('my-clinics', [PetClinicController::class, 'myList']);
        Route::put('clinics/{id}', [PetClinicController::class, 'update']);

        // My Products
        Route::post('products', [PetProductController::class, 'store']);
        Route::put('products/{id}', [PetProductController::class, 'update']);
        Route::delete('products/{id}', [PetProductController::class, 'destroy']);

        // Reviews
        Route::post('reviews', [PetReviewController::class, 'store']);
        Route::put('reviews/{id}', [PetReviewController::class, 'update']);
        Route::delete('reviews/{id}', [PetReviewController::class, 'destroy']);

        // Medical Records
        Route::get('medical-records', [PetAppointmentController::class, 'getMedicalRecords']);
        Route::post('medical-records', [PetAppointmentController::class, 'createMedicalRecord']);

        // My Reviews
        Route::get('my-reviews', [PetReviewController::class, 'myReviews']);

        // Statistics
        Route::get('stats/appointments', [PetAppointmentController::class, 'stats']);
        Route::get('stats/clinic/{id}', [PetClinicController::class, 'stats']);
    });

    // ADMIN ROUTES
    Route::middleware(['auth:sanctum', 'tenant', 'role:admin'])->prefix('admin')->group(function () {
        // Clinic Management
        Route::post('clinics/{id}/verify', [PetClinicController::class, 'verify']);
        Route::delete('clinics/{id}', [PetClinicController::class, 'delete']);

        // Review Management
        Route::post('reviews/{id}/approve', [PetReviewController::class, 'approve']);
        Route::post('reviews/{id}/reject', [PetReviewController::class, 'reject']);

        // Analytics
        Route::get('analytics/clinics', [PetClinicController::class, 'analyticsClinic']);
        Route::get('analytics/appointments', [PetAppointmentController::class, 'analyticsAdmin']);
        Route::get('analytics/boarding', [PetBoardingController::class, 'analyticsAdmin']);

        // Earnings
        Route::get('earnings/clinic/{id}', [PetClinicController::class, 'earnings']);
        Route::get('earnings/report', [PetClinicController::class, 'earningsReport']);
    });
});

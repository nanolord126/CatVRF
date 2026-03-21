<?php declare(strict_types=1);

use App\Domains\Medical\Http\Controllers\MedicalClinicController;
use App\Domains\Medical\Http\Controllers\MedicalDoctorController;
use App\Domains\Medical\Http\Controllers\MedicalAppointmentController;
use App\Domains\Medical\Http\Controllers\MedicalReviewController;
use App\Domains\Medical\Http\Controllers\MedicalTestOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('medical')->group(function () {
    // Public endpoints
    Route::get('clinics', [MedicalClinicController::class, 'index']);
    Route::get('clinics/{id}', [MedicalClinicController::class, 'show']);
    Route::get('clinics/{id}/doctors', [MedicalClinicController::class, 'doctors']);
    Route::get('clinics/{id}/services', [MedicalClinicController::class, 'services']);
    Route::get('clinics/{id}/reviews', [MedicalClinicController::class, 'reviews']);
    Route::get('doctors', [MedicalDoctorController::class, 'index']);
    Route::get('doctors/{id}', [MedicalDoctorController::class, 'show']);
    Route::get('doctors/{id}/reviews', [MedicalDoctorController::class, 'reviews']);
    Route::get('services', [MedicalAppointmentController::class, 'services']);
    Route::get('search', [MedicalClinicController::class, 'search']);

    // Auth endpoints
    Route::middleware('auth:api')->group(function () {
        // Clinic management
        Route::post('clinics', [MedicalClinicController::class, 'store']);
        Route::get('my-clinic', [MedicalClinicController::class, 'myClinic']);
        Route::patch('clinics/{id}', [MedicalClinicController::class, 'update']);
        Route::delete('clinics/{id}', [MedicalClinicController::class, 'delete']);

        // Doctor management
        Route::post('doctors', [MedicalDoctorController::class, 'store']);
        Route::get('my-profile', [MedicalDoctorController::class, 'myProfile']);
        Route::patch('doctors/{id}', [MedicalDoctorController::class, 'update']);
        Route::delete('doctors/{id}', [MedicalDoctorController::class, 'delete']);

        // Appointments
        Route::post('appointments', [MedicalAppointmentController::class, 'store']);
        Route::get('my-appointments', [MedicalAppointmentController::class, 'myAppointments']);
        Route::get('appointments/{id}', [MedicalAppointmentController::class, 'show']);
        Route::patch('appointments/{id}', [MedicalAppointmentController::class, 'update']);
        Route::patch('appointments/{id}/cancel', [MedicalAppointmentController::class, 'cancel']);
        Route::patch('appointments/{id}/complete', [MedicalAppointmentController::class, 'complete']);
        Route::get('appointments/{id}/history', [MedicalAppointmentController::class, 'history']);

        // Reviews
        Route::post('doctors/{id}/reviews', [MedicalReviewController::class, 'store']);
        Route::get('doctors/{id}/reviews', [MedicalReviewController::class, 'doctorReviews']);
        Route::patch('reviews/{id}', [MedicalReviewController::class, 'update']);
        Route::delete('reviews/{id}', [MedicalReviewController::class, 'delete']);
        Route::post('reviews/{id}/helpful', [MedicalReviewController::class, 'markHelpful']);

        // Test orders
        Route::post('test-orders', [MedicalTestOrderController::class, 'store']);
        Route::get('my-test-orders', [MedicalTestOrderController::class, 'myTestOrders']);
        Route::get('test-orders/{id}', [MedicalTestOrderController::class, 'show']);
        Route::patch('test-orders/{id}/cancel', [MedicalTestOrderController::class, 'cancel']);

        // Prescriptions
        Route::get('my-prescriptions', [MedicalAppointmentController::class, 'myPrescriptions']);
        Route::get('prescriptions/{id}', [MedicalAppointmentController::class, 'getPrescription']);

        // Medical records
        Route::get('my-records', [MedicalAppointmentController::class, 'myRecords']);
        Route::get('records/{id}', [MedicalAppointmentController::class, 'getRecord']);
    });

    // Admin endpoints
    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::post('clinics/{id}/verify', [MedicalClinicController::class, 'verify']);
        Route::get('clinics-all', [MedicalClinicController::class, 'all']);
        Route::get('analytics/clinics', [MedicalClinicController::class, 'analytics']);

        Route::get('doctors-all', [MedicalDoctorController::class, 'all']);
        Route::get('analytics/doctors', [MedicalDoctorController::class, 'analytics']);

        Route::get('appointments-all', [MedicalAppointmentController::class, 'all']);
        Route::patch('appointments/{id}/status', [MedicalAppointmentController::class, 'updateStatus']);
        Route::get('analytics/appointments', [MedicalAppointmentController::class, 'analytics']);

        Route::get('reviews-all', [MedicalReviewController::class, 'all']);
        Route::patch('reviews/{id}/approve', [MedicalReviewController::class, 'approve']);
        Route::delete('reviews/{id}/reject', [MedicalReviewController::class, 'reject']);
        Route::get('analytics/reviews', [MedicalReviewController::class, 'analytics']);

        Route::get('test-orders-all', [MedicalTestOrderController::class, 'all']);
        Route::patch('test-orders/{id}/complete', [MedicalTestOrderController::class, 'complete']);
        Route::get('analytics/test-orders', [MedicalTestOrderController::class, 'analytics']);
    });
});

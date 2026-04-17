<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Education\LearningPathController;
use App\Http\Controllers\Api\Education\DynamicPricingController;
use App\Http\Controllers\Api\Education\SlotBookingController;
use App\Http\Controllers\Api\Education\FraudDetectionController;
use App\Http\Controllers\Api\Education\LiveClassController;
use App\Http\Controllers\Api\Education\CourseEnrollmentController;

Route::middleware(['auth:sanctum', 'tenant', 'b2c-b2b', 'rate-limit', 'fraud-check'])->group(function () {
    Route::post('/api/v1/education/learning-paths/generate', [LearningPathController::class, 'generate']);
    Route::post('/api/v1/education/learning-paths/adapt/{enrollment}', [LearningPathController::class, 'adapt']);
    
    Route::post('/api/v1/education/pricing/calculate', [DynamicPricingController::class, 'calculate']);
    Route::post('/api/v1/education/pricing/flash-sale/{courseId}', [DynamicPricingController::class, 'triggerFlashSale']);
    
    Route::post('/api/v1/education/slots/{slotId}/hold', [SlotBookingController::class, 'hold']);
    Route::post('/api/v1/education/slots/{slotId}/release', [SlotBookingController::class, 'release']);
    Route::post('/api/v1/education/slots/book', [SlotBookingController::class, 'book']);
    Route::post('/api/v1/education/bookings/{bookingId}/cancel', [SlotBookingController::class, 'cancel']);
    
    Route::post('/api/v1/education/fraud/cheating/{enrollmentId}', [FraudDetectionController::class, 'detectCheating']);
    Route::post('/api/v1/education/fraud/review/{reviewId}', [FraudDetectionController::class, 'detectReviewFraud']);
    
    Route::post('/api/v1/education/live-classes/{slotId}/create-session', [LiveClassController::class, 'createSession']);
    Route::post('/api/v1/education/live-classes/{sessionId}/join', [LiveClassController::class, 'joinSession']);
    Route::post('/api/v1/education/live-classes/{sessionId}/start', [LiveClassController::class, 'startSession']);
    Route::post('/api/v1/education/live-classes/{sessionId}/end', [LiveClassController::class, 'endSession']);
    Route::post('/api/v1/education/live-classes/{sessionId}/chat', [LiveClassController::class, 'sendChatMessage']);
    Route::get('/api/v1/education/live-classes/{sessionId}/chat', [LiveClassController::class, 'getChatHistory']);
    Route::post('/api/v1/education/live-classes/{sessionId}/ai-assist', [LiveClassController::class, 'triggerAIAssistance']);
    
    Route::post('/api/v1/education/enrollments', [CourseEnrollmentController::class, 'enroll']);
    Route::post('/api/v1/education/enrollments/{enrollmentId}/progress', [CourseEnrollmentController::class, 'updateProgress']);
    Route::post('/api/v1/education/enrollments/{enrollmentId}/cancel', [CourseEnrollmentController::class, 'cancelEnrollment']);
    Route::post('/api/v1/education/enrollments/{enrollmentId}/certificate', [CourseEnrollmentController::class, 'issueCertificate']);
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApprovalController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/approvals')->group(function () {
    // Get my pending approvals
    Route::get('/pending', [ApprovalController::class, 'getPendingApprovals']);

    // Get my approval requests
    Route::get('/my-requests', [ApprovalController::class, 'getMyRequests']);

    // Get approval request details
    Route::get('/{approvalRequest}', [ApprovalController::class, 'show']);

    // Approve request
    Route::post('/{approvalRequest}/approve', [ApprovalController::class, 'approve']);

    // Reject request
    Route::post('/{approvalRequest}/reject', [ApprovalController::class, 'reject']);

    // Comment on request
    Route::post('/{approvalRequest}/comment', [ApprovalController::class, 'comment']);

    // Get eligible approvers
    Route::get('/{approvalRequest}/eligible-approvers', [ApprovalController::class, 'getEligibleApprovers']);

    // Escalate request (manual)
    Route::post('/{approvalRequest}/escalate', [ApprovalController::class, 'escalate']);
});

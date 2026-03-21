<?php declare(strict_types=1);

use App\Domains\Freelance\Http\Controllers\FreelanceJobController;
use App\Domains\Freelance\Http\Controllers\FreelancerController;
use App\Domains\Freelance\Http\Controllers\ProposalController;
use App\Domains\Freelance\Http\Controllers\ContractController;
use App\Domains\Freelance\Http\Controllers\DeliverableController;
use App\Domains\Freelance\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function () {
    Route::prefix('freelance')->group(function () {
        Route::get('/freelancers', [FreelancerController::class, 'index'])->name('freelancers.index');
        Route::get('/freelancers/{id}', [FreelancerController::class, 'show'])->name('freelancers.show');
        Route::get('/jobs', [FreelanceJobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/{id}', [FreelanceJobController::class, 'show'])->name('jobs.show');

        Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
            Route::post('/register-freelancer', [FreelancerController::class, 'register'])->name('freelancers.register');
            Route::put('/freelancers/{id}', [FreelancerController::class, 'update'])->name('freelancers.update');
            Route::delete('/freelancers/{id}', [FreelancerController::class, 'destroy'])->name('freelancers.destroy');

            Route::post('/jobs', [FreelanceJobController::class, 'store'])->name('jobs.store');
            Route::put('/jobs/{id}', [FreelanceJobController::class, 'update'])->name('jobs.update');
            Route::delete('/jobs/{id}', [FreelanceJobController::class, 'destroy'])->name('jobs.destroy');
            Route::post('/jobs/{id}/close', [FreelanceJobController::class, 'close'])->name('jobs.close');
            Route::get('/my-jobs', [FreelanceJobController::class, 'myJobs'])->name('jobs.my');

            Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
            Route::put('/proposals/{id}', [ProposalController::class, 'update'])->name('proposals.update');
            Route::delete('/proposals/{id}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
            Route::post('/proposals/{id}/accept', [ProposalController::class, 'accept'])->name('proposals.accept');
            Route::post('/proposals/{id}/reject', [ProposalController::class, 'reject'])->name('proposals.reject');
            Route::get('/my-proposals', [ProposalController::class, 'myProposals'])->name('proposals.my');
            Route::get('/job/{jobId}/proposals', [ProposalController::class, 'jobProposals'])->name('proposals.job');

            Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
            Route::get('/contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
            Route::post('/contracts/{id}/release-milestone', [ContractController::class, 'releaseMilestone'])->name('contracts.releaseMilestone');
            Route::post('/contracts/{id}/complete', [ContractController::class, 'complete'])->name('contracts.complete');
            Route::post('/contracts/{id}/pause', [ContractController::class, 'pause'])->name('contracts.pause');
            Route::post('/contracts/{id}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');
            Route::get('/my-contracts', [ContractController::class, 'myContracts'])->name('contracts.my');

            Route::post('/deliverables', [DeliverableController::class, 'store'])->name('deliverables.store');
            Route::get('/deliverables/{id}', [DeliverableController::class, 'show'])->name('deliverables.show');
            Route::post('/deliverables/{id}/approve', [DeliverableController::class, 'approve'])->name('deliverables.approve');
            Route::post('/deliverables/{id}/request-revision', [DeliverableController::class, 'requestRevision'])->name('deliverables.requestRevision');
            Route::post('/deliverables/{id}/reject', [DeliverableController::class, 'reject'])->name('deliverables.reject');
            Route::get('/contract/{contractId}/deliverables', [DeliverableController::class, 'contractDeliverables'])->name('deliverables.contract');

            Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
            Route::get('/freelancers/{id}/reviews', [ReviewController::class, 'freelancerReviews'])->name('reviews.freelancer');
            Route::get('/contracts/{id}/reviews', [ReviewController::class, 'contractReviews'])->name('reviews.contract');

            Route::post('/reviews/{id}/helpful', [ReviewController::class, 'markHelpful'])->name('reviews.helpful');
            Route::post('/reviews/{id}/unhelpful', [ReviewController::class, 'markUnhelpful'])->name('reviews.unhelpful');
        });

        Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
            Route::get('/stats', [FreelanceJobController::class, 'stats'])->name('stats');
            Route::get('/earnings/report', [ContractController::class, 'earningsReport'])->name('earningsReport');
            Route::get('/top-freelancers', [FreelancerController::class, 'topFreelancers'])->name('topFreelancers');
            Route::post('/freelancers/{id}/verify', [FreelancerController::class, 'verify'])->name('freelancers.verify');
        });
    });
});

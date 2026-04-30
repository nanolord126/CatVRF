<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Tender API Routes (Shared across all verticals)
|--------------------------------------------------------------------------
|
| B2B Tender system for all verticals with shared infrastructure
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // ========================
    // BUSINESS ENDPOINTS
    // ========================
    Route::prefix('business/tenders')->middleware(['can:business'])->group(function () {
        Route::get('/', function (Request $request, App\Services\TenderService $tenderService) {
            $status = $request->query('status');
            $businessId = $request->user()->id;
            
            return response()->json($tenderService->listTenders($businessId, $status));
        })->name('tender.business.list');
        
        Route::post('/', function (Request $request, App\Services\TenderService $tenderService) {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:supply,one_time',
                'min_amount' => 'required|numeric|min:100000',
                'duration_months' => 'nullable|integer|min:3|required_if:type,supply',
                'ends_at' => 'required|date|after:now',
                'delivery_start_date' => 'nullable|date',
                'delivery_end_date' => 'nullable|date|after:delivery_start_date',
                'requirements' => 'nullable|array',
                'delivery_terms' => 'nullable|array',
                'payment_terms' => 'nullable|array',
            ]);
            
            $tender = $tenderService->createTender(
                $request->all(),
                $request->user()->id,
                $request->user()->tenant_id,
                $request->input('vertical_id')
            );
            
            return response()->json($tender, 201);
        })->name('tender.business.create');
        
        Route::get('/{tenderId}', function (int $tenderId, Request $request, App\Services\TenderService $tenderService) {
            $businessId = $request->user()->id;
            
            return response()->json($tenderService->getTenderWithBids($tenderId, $businessId));
        })->name('tender.business.show');
        
        Route::post('/{tenderId}/activate', function (int $tenderId, Request $request, App\Services\TenderService $tenderService) {
            $businessId = $request->user()->id;
            
            $tender = $tenderService->activateTender($tenderId, $businessId);
            
            return response()->json($tender);
        })->name('tender.business.activate');
        
        Route::post('/{tenderId}/request-guarantee', function (
            int $tenderId,
            Request $request,
            App\Services\PlatformGuaranteeService $guaranteeService
        ) {
            $businessId = $request->user()->id;
            
            $result = $guaranteeService->requestPlatformGuarantee($tenderId, $businessId);
            
            return response()->json($result);
        })->name('tender.business.request-guarantee');
        
        Route::post('/{tenderId}/pay-guarantee-fee', function (
            int $tenderId,
            Request $request,
            App\Services\PlatformGuaranteeService $guaranteeService
        ) {
            $businessId = $request->user()->id;
            
            $tender = $guaranteeService->payGuaranteeFee($tenderId, $businessId);
            
            return response()->json($tender);
        })->name('tender.business.pay-guarantee-fee');
        
        Route::post('/{tenderId}/close', function (int $tenderId, Request $request, App\Services\TenderService $tenderService) {
            $businessId = $request->user()->id;
            
            $tender = $tenderService->closeTender($tenderId, $businessId);
            
            return response()->json($tender);
        })->name('tender.business.close');
        
        Route::post('/{tenderId}/assign-manager/{managerId}', function (
            int $tenderId,
            int $managerId,
            Request $request,
            App\Services\TenderService $tenderService
        ) {
            $tender = App\Models\Tender::findOrFail($tenderId);
            $tender->manager_id = $managerId;
            $tender->save();
            
            return response()->json($tender);
        })->name('tender.business.assign-manager');
        
        Route::post('/{tenderId}/select-winner/{bidId}', function (
            int $tenderId,
            int $bidId,
            Request $request,
            App\Services\TenderService $tenderService
        ) {
            $businessId = $request->user()->id;
            
            $tender = $tenderService->selectWinningBid($tenderId, $bidId, $businessId);
            
            return response()->json([
                'tender' => $tender,
                'documents' => $tenderService->getTenderDocuments($tenderId),
            ]);
        })->name('tender.business.select-winner');
        
        Route::post('/{tenderId}/bids/{bidId}/review', function (
            int $tenderId,
            int $bidId,
            Request $request,
            App\Services\SupplierTenderService $tenderService
        ) {
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:2000',
                'is_public' => 'boolean',
            ]);
            
            $review = $tenderService->addReview(
                $tenderId,
                $bidId,
                $request->all(),
                $request->user()->id
            );
            
            return response()->json($review, 201);
        })->name('tender.business.review');
    });
    
    // ========================
    // SUPPLIER ENDPOINTS
    // ========================
    Route::prefix('supplier/tenders')->middleware(['can:supplier'])->group(function () {
        Route::get('/available', function (Request $request, App\Services\SupplierTenderService $tenderService) {
            $tenantId = $request->user()->tenant_id;
            $verticalId = $request->query('vertical_id');
            
            return response()->json($tenderService->listAvailableTenders($tenantId, $verticalId));
        })->name('tender.supplier.available');
        
        Route::get('/my-bids', function (Request $request, App\Services\SupplierTenderService $tenderService) {
            $status = $request->query('status');
            $supplierId = $request->user()->id;
            
            return response()->json($tenderService->listMyBids($supplierId, $status));
        })->name('tender.supplier.my-bids');
        
        Route::post('/{tenderId}/bids', function (int $tenderId, Request $request, App\Services\SupplierTenderService $tenderService) {
            $request->validate([
                'bid_amount' => 'required|numeric|min:100000',
                'proposal' => 'nullable|string|max:5000',
                'terms' => 'nullable|array',
            ]);
            
            $bid = $tenderService->submitBid(
                $tenderId,
                $request->all(),
                $request->user()->id,
                $request->user()->tenant_id
            );
            
            return response()->json($bid, 201);
        })->name('tender.supplier.submit-bid');
        
        Route::post('/bids/{bidId}/withdraw', function (int $bidId, Request $request, App\Services\SupplierTenderService $tenderService) {
            $supplierId = $request->user()->id;
            
            $bid = $tenderService->withdrawBid($bidId, $supplierId);
            
            return response()->json($bid);
        })->name('tender.supplier.withdraw-bid');
        
        Route::get('/check-participation', function (Request $request, App\Services\SupplierTenderService $tenderService) {
            $supplierId = $request->user()->id;
            $tenantId = $request->user()->tenant_id;
            
            return response()->json($tenderService->canParticipate($supplierId, $tenantId));
        })->name('tender.supplier.check-participation');
    });
    
    // ========================
    // PUBLIC ENDPOINTS (ANONYMIZED)
    // ========================
    Route::prefix('public/tenders')->group(function () {
        Route::get('/statistics/{verticalId}', function (int $verticalId, Request $request) {
            // Return aggregated statistics for a vertical (completely anonymized)
            $stats = App\Models\TenderStatistic::where('vertical_id', $verticalId)
                ->selectRaw('
                    COUNT(*) as total_suppliers,
                    AVG(total_participated) as avg_participated,
                    AVG(total_won) as avg_won,
                    AVG(average_rating) as avg_rating,
                    SUM(total_value_won) as total_value_won
                ')
                ->first();
            
            return response()->json($stats);
        })->name('tender.public.vertical-statistics');
        
        Route::get('/suppliers/{supplierId}/reviews/{verticalId}', function (
            int $supplierId,
            int $verticalId,
            Request $request,
            App\Services\SupplierTenderService $tenderService
        ) {
            $tenantId = $request->query('tenant_id');
            
            return response()->json($tenderService->getPublicReviews($supplierId, $tenantId));
        })->name('tender.public.supplier-reviews');
    });
    
    // ========================
    // B2B DOCUMENT ENDPOINTS (Shared)
    // ========================
    Route::prefix('b2b/documents')->group(function () {
        Route::post('/tenders/{tenderId}/bids/{bidId}/generate', function (
            int $tenderId,
            int $bidId,
            Request $request,
            App\Services\B2BDocumentService $documentService
        ) {
            $documents = $documentService->generateTenderCompletionDocuments(
                $tenderId,
                $bidId,
                $request->user()->id
            );
            
            return response()->json($documents);
        })->name('b2b.documents.tender.generate');
        
        Route::post('/orders/{orderId}/generate', function (
            int $orderId,
            Request $request,
            App\Services\B2BDocumentService $documentService
        ) {
            $request->validate([
                'business_id' => 'required|integer',
                'supplier_id' => 'required|integer',
                'tenant_id' => 'required|integer',
                'vertical_id' => 'required|integer',
                'amount' => 'required|numeric',
            ]);
            
            $documents = $documentService->generateB2BOrderDocuments(
                $orderId,
                $request->business_id,
            );
            
            return response()->json($documents);
        })->name('b2b.documents.order.generate');
        
        Route::post('/tenders/{tenderId}/release-hold', function (
            int $tenderId,
            Request $request,
            App\Services\PlatformGuaranteeService $guaranteeService
        ) {
            $result = $guaranteeService->withdraw(
                $tenderId,
                $request->supplier_id,
                $request->tenant_id,
                $request->vertical_id,
                $request->amount,
                $request->user()->id
            );
            
            return response()->json($result);
        })->name('tender.release-hold');
        
        Route::post('/{type}/{documentId}/sign', function (
            string $type,
            int $documentId,
            Request $request,
            App\Services\B2BDocumentService $documentService
        ) {
            if (!in_array($type, ['tender', 'b2b'])) {
                return response()->json(['error' => 'Invalid document type'], 422);
            }
            
            $document = $documentService->signDocument($type, $documentId, $request->user()->id);
            
            return response()->json($document);
        })->name('b2b.documents.sign');
    });
});

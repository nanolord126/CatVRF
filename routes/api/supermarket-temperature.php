<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Modules\Supermarket\Application\Services\TemperatureMonitoringService;
use Modules\Supermarket\Application\Services\TemperatureAlertService;
use Modules\Supermarket\Application\Services\TemperatureCRMIntegrationService;
use Modules\Supermarket\Application\Services\TemperatureDashboardService;

/**
 * Temperature Monitoring API Routes
 * 
 * REST API endpoints for temperature monitoring, device management,
 * and CRM integration for supermarket vertical
 */

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // Device Management
    Route::prefix('temperature/devices')->group(function () {
        Route::post('/register', [TemperatureMonitoringService::class, 'registerDevice'])
            ->name('supermarket.temperature.devices.register');
        
        Route::get('/{deviceId}/status', [TemperatureMonitoringService::class, 'getDeviceStatus'])
            ->name('supermarket.temperature.devices.status');
        
        Route::get('/{deviceId}/history', [TemperatureMonitoringService::class, 'getTemperatureHistory'])
            ->name('supermarket.temperature.devices.history');
        
        Route::post('/{deviceId}/calibration', [TemperatureMonitoringService::class, 'updateCalibration'])
            ->name('supermarket.temperature.devices.calibration');
        
        Route::post('/{deviceId}/deactivate', [TemperatureMonitoringService::class, 'deactivateDevice'])
            ->name('supermarket.temperature.devices.deactivate');
    });
    
    // Telemetry Ingestion (public endpoint with device authentication)
    Route::post('/temperature/ingest/{accessKey}', [TemperatureMonitoringService::class, 'ingestReading'])
        ->name('supermarket.temperature.ingest');
    
    // Alerts
    Route::prefix('temperature/alerts')->group(function () {
        Route::get('/active', [TemperatureAlertService::class, 'getActiveAlerts'])
            ->name('supermarket.temperature.alerts.active');
        
        Route::post('/{readingId}/resolve', [TemperatureAlertService::class, 'resolveAlert'])
            ->name('supermarket.temperature.alerts.resolve');
    });
    
    // CRM Integration (internal)
    Route::prefix('temperature/crm')->middleware(['can:admin'])->group(function () {
        Route::post('/sync/violation/{readingId}', function ($readingId, TemperatureCRMIntegrationService $crm) {
            $reading = \Modules\Supermarket\Domain\Models\TemperatureReading::findOrFail($readingId);
            $device = $reading->device;
            $requirement = $reading->productRequirement;
            
            if (!$requirement) {
                return response()->json(['error' => 'No requirement found'], 404);
            }
            
            $success = $crm->syncViolationToCRM($reading, $device, $requirement);
            return response()->json(['success' => $success]);
        })->name('supermarket.temperature.crm.sync.violation');
        
        Route::post('/sync/device/{deviceId}', function ($deviceId, TemperatureCRMIntegrationService $crm) {
            $device = \Modules\Supermarket\Domain\Models\TemperatureMonitoringDevice::findOrFail($deviceId);
            $success = $crm->syncDeviceToCRM($device);
            return response()->json(['success' => $success]);
        })->name('supermarket.temperature.crm.sync.device');
        
        Route::post('/sync/aggregated', function (Request $request, TemperatureCRMIntegrationService $crm) {
            $request->validate([
                'tenant_id' => 'required|integer',
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);
            
            $success = $crm->syncAggregatedDataToCRM(
                $request->tenant_id,
                \Carbon\Carbon::parse($request->from),
                \Carbon\Carbon::parse($request->to)
            );
            
            return response()->json(['success' => $success]);
        })->name('supermarket.temperature.crm.sync.aggregated');
    });
    
    // Temperature Requirements
    Route::prefix('temperature/requirements')->group(function () {
        Route::get('/', function () {
            return \Modules\Supermarket\Domain\Models\ProductTemperatureRequirement::with('product')
                ->get();
        })->name('supermarket.temperature.requirements.index');
        
        Route::post('/', function (Request $request) {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'tenant_id' => 'nullable|exists:tenants,id',
                'min_temperature_celsius' => 'nullable|numeric',
                'max_temperature_celsius' => 'nullable|numeric',
                'optimal_temperature_celsius' => 'nullable|numeric',
                'storage_type' => 'required|in:frozen,refrigerated,ambient,heated',
                'critical_min_temperature_celsius' => 'nullable|numeric',
                'critical_max_temperature_celsius' => 'nullable|numeric',
                'max_duration_outside_range_minutes' => 'nullable|integer',
                'warning_threshold_minutes' => 'nullable|integer',
                'requires_continuous_monitoring' => 'boolean',
                'is_hazardous' => 'boolean',
                'notes' => 'nullable|string',
                'regulation_reference' => 'nullable|string',
            ]);
            
            return \Modules\Supermarket\Domain\Models\ProductTemperatureRequirement::create($validated);
        })->name('supermarket.temperature.requirements.store');
        
        Route::get('/{id}', function ($id) {
            return \Modules\Supermarket\Domain\Models\ProductTemperatureRequirement::with('product')
                ->findOrFail($id);
        })->name('supermarket.temperature.requirements.show');
        
        Route::put('/{id}', function ($id, Request $request) {
            $requirement = \Modules\Supermarket\Domain\Models\ProductTemperatureRequirement::findOrFail($id);
            $requirement->update($request->all());
            return $requirement;
        })->name('supermarket.temperature.requirements.update');
        
        Route::delete('/{id}', function ($id) {
            $requirement = \Modules\Supermarket\Domain\Models\ProductTemperatureRequirement::findOrFail($id);
            $requirement->delete();
            return response()->json(['success' => true]);
        })->name('supermarket.temperature.requirements.destroy');
    });
    
    // Temperature Readings
    Route::prefix('temperature/readings')->group(function () {
        Route::get('/', function (Request $request) {
            $query = \Modules\Supermarket\Domain\Models\TemperatureReading::with(['device', 'productRequirement']);
            
            if ($request->has('device_id')) {
                $query->where('device_id', $request->device_id);
            }
            
            if ($request->has('from') && $request->has('to')) {
                $query->whereBetween('recorded_at', [
                    \Carbon\Carbon::parse($request->from),
                    \Carbon\Carbon::parse($request->to)
                ]);
            }
            
            if ($request->has('is_violation')) {
                $query->where('is_violation', $request->boolean('is_violation'));
            }
            
            if ($request->has('violation_severity')) {
                $query->where('violation_severity', $request->violation_severity);
            }
            
            return $query->orderBy('recorded_at', 'desc')->paginate(50);
        })->name('supermarket.temperature.readings.index');
        
        Route::get('/{id}', function ($id) {
            return \Modules\Supermarket\Domain\Models\TemperatureReading::with(['device', 'productRequirement'])
                ->findOrFail($id);
        })->name('supermarket.temperature.readings.show');
    });
    
    // Dashboard Data
    Route::prefix('temperature/dashboard')->group(function () {
        Route::get('/summary', function (Request $request, TemperatureDashboardService $dashboard) {
            return $dashboard->getDashboardSummary($request->user()?->tenant_id);
        })->name('supermarket.temperature.dashboard.summary');
        
        Route::get('/chart', function (Request $request, TemperatureDashboardService $dashboard) {
            return $dashboard->getTemperatureChart(
                $request->user()?->tenant_id,
                $request->device_id,
                (int)($request->hours ?? 24)
            );
        })->name('supermarket.temperature.dashboard.chart');
        
        Route::get('/devices', function (Request $request, TemperatureDashboardService $dashboard) {
            return $dashboard->getDevicesList($request->user()?->tenant_id);
        })->name('supermarket.temperature.dashboard.devices');
        
        Route::get('/violations', function (Request $request, TemperatureDashboardService $dashboard) {
            return $dashboard->getRecentViolations(
                $request->user()?->tenant_id,
                (int)($request->limit ?? 20)
            );
        })->name('supermarket.temperature.dashboard.violations');
        
        Route::get('/zones', function (Request $request, TemperatureDashboardService $dashboard) {
            return $dashboard->getZoneStatistics($request->user()?->tenant_id);
        })->name('supermarket.temperature.dashboard.zones');
        
        Route::get('/compliance-report', function (Request $request, TemperatureDashboardService $dashboard) {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);
            
            return $dashboard->getComplianceReport(
                $request->user()?->tenant_id,
                \Carbon\Carbon::parse($request->from),
                \Carbon\Carbon::parse($request->to)
            );
        })->name('supermarket.temperature.dashboard.compliance');
    });
    
    // Supplier Payment Delay Management
    Route::prefix('supplier/payment-delay')->middleware(['can:supplier'])->group(function () {
        Route::post('/documents/{documentId}', function (
            Request $request,
            int $documentId,
            Modules\Supermarket\Application\Services\DocumentManagementService $documentService,
            App\Services\PaymentDelayValidationService $delayValidation
        ) {
            $request->validate([
                'delay_days' => 'required|integer|min:1|max:14',
                'inn' => 'required|string|size:12',
                'price_markup_percent' => 'nullable|numeric|min:0|max:10',
            ]);
            
            $result = $documentService->setPaymentDelay(
                $documentId,
                $request->delay_days,
                $request->inn,
                $request->user()->id,
                $request->user()->tenant_id,
                $request->user()->id,
                $request->price_markup_percent
            );
            
            if (!$result['allowed']) {
                return response()->json(['error' => $result['reason']], 422);
            }
            
            return response()->json($result);
        })->name('supermarket.supplier.payment-delay.set');
        
        Route::post('/extended-request/{documentId}', function (
            Request $request,
            int $documentId,
            Modules\Supermarket\Application\Services\DocumentManagementService $documentService
        ) {
            $request->validate([
                'delay_days' => 'required|integer|min:1',
                'inn' => 'required|string|size:12',
                'reason' => 'nullable|string|max:1000',
            ]);
            
            $result = $documentService->requestExtendedPaymentDelay(
                $documentId,
                $request->delay_days,
                $request->inn,
                $request->user()->id,
                $request->user()->tenant_id,
                $request->user()->id,
                $request->reason
            );
            
            return response()->json($result);
        })->name('supermarket.supplier.payment-delay.request');
    });
    
    // Admin Payment Delay Request Management
    Route::prefix('admin/payment-delay-requests')->middleware(['can:admin'])->group(function () {
        Route::get('/', function (Request $request, App\Services\PaymentDelayValidationService $delayValidation) {
            $requests = App\Models\SupplierDelayRequest::with(['supplier', 'tenant', 'requestedBy'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return response()->json($requests);
        })->name('supermarket.admin.payment-delay-requests.index');
        
        Route::post('/{requestId}/approve', function (
            int $requestId,
            Request $request,
            App\Services\PaymentDelayValidationService $delayValidation
        ) {
            $request->validate([
                'admin_notes' => 'nullable|string',
                'validity_days' => 'nullable|integer|min:1',
            ]);
            
            $success = $delayValidation->approveDelayRequest(
                $requestId,
                $request->user()->id,
                $request->admin_notes,
                $request->validity_days
            );
            
            if (!$success) {
                return response()->json(['error' => 'Failed to approve request'], 422);
            }
            
            return response()->json(['success' => true]);
        })->name('supermarket.admin.payment-delay-requests.approve');
        
        Route::post('/{requestId}/reject', function (
            int $requestId,
            Request $request,
            App\Services\PaymentDelayValidationService $delayValidation
        ) {
            $request->validate([
                'admin_notes' => 'nullable|string',
            ]);
            
            $success = $delayValidation->rejectDelayRequest(
                $requestId,
                $request->user()->id,
                $request->admin_notes
            );
            
            if (!$success) {
                return response()->json(['error' => 'Failed to reject request'], 422);
            }
            
            return response()->json(['success' => true]);
        })->name('supermarket.admin.payment-delay-requests.reject');
    });
    
    // B2B Product Card - Payment Delay Info
    Route::get('/b2b/products/{productId}/payment-delay-info', function (
        int $productId,
        Request $request
    ) {
        $documents = Modules\Supermarket\Domain\Models\Document::where('product_id', $productId)
            ->where('payment_delay_days', '>', 0)
            ->where(function ($q) {
                $q->whereNull('payment_delay_until')
                  ->orWhere('payment_delay_until', '>', now());
            })
            ->get();
        
        $delayInfo = $documents->map(function ($doc) {
            return [
                'document_id' => $doc->id,
                'document_number' => $doc->document_number,
                'batch_number' => $doc->batch_number,
                'payment_delay_days' => $doc->payment_delay_days,
                'payment_delay_until' => $doc->payment_delay_until?->toIso8601String(),
                'original_price' => $doc->batch_weight,
                'price_with_delay' => $doc->price_with_delay,
                'price_markup_percent' => $doc->price_markup_percent,
                'markup_recommendation' => $doc->getPriceMarkupRecommendation(),
            ];
        });
        
        return response()->json([
            'product_id' => $productId,
            'has_delay_options' => $delayInfo->count() > 0,
            'delay_options' => $delayInfo,
        ]);
    })->name('supermarket.b2b.products.payment-delay-info');
});

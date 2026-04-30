<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Traits\Guards\HighRiskActionGuard;
use Psr\Log\LoggerInterface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterTenantRequest;
use App\Http\Requests\Api\V1\Auth\VerifyDocumentsRequest;
use App\Services\Auth\TenantOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Tenant;

final class TenantController extends Controller
{use HighRiskActionGuard;
    
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly TenantOnboardingService $tenantOnboarding,) {}

    /**
     * Register new tenant (business)
     * POST /api/v1/tenants/register
     */
    public function register(RegisterTenantRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? $request->correlation_id ?? Str::uuid()->toString();

        try {
            $tenant = $this->tenantOnboarding->registerTenant([
                'name' => $request->name,
                'type' => $request->type,
                'inn' => $request->inn,
                'kpp' => $request->kpp,
                'ogrn' => $request->ogrn,
                'legal_entity_type' => $request->legal_entity_type,
                'legal_address' => $request->legal_address,
                'actual_address' => $request->actual_address,
                'phone' => $request->phone,
                'email' => $request->email,
                'website' => $request->website,
                'user_id' => $request->user_id,
                'timezone' => $request->timezone,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'message' => 'Tenant registered successfully. Verification in progress',
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'inn' => $tenant->inn,
                    'verification_status' => $tenant->verification_status->value,
                    'is_active' => $tenant->is_active,
                    'is_verified' => $tenant->is_verified,
                ],
                'correlation_id' => $correlationId,
            ], 201);
        } catch (ValidationException $e) {
            $this->log->channel('audit')->error('Tenant registration validation failed', [
                'error' => $e->getMessage(),
                'inn' => $request->inn,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
                'correlation_id' => $correlationId,
            ], 422);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Tenant registration failed', [
                'error' => $e->getMessage(),
                'inn' => $request->inn,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => 'Tenant registration failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Verify tenant documents
     * POST /api/v1/tenants/{tenant}/verify-documents
     */
    public function verifyDocuments(VerifyDocumentsRequest $request, string $tenantId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $tenant = Tenant::findOrFail($tenantId);

        // Store documents and get paths
        $documents = [];
        if ($request->hasFile('inn_document')) {
            $documents['inn_document'] = $request->file('inn_document')->store('tenant-documents/'.$tenantId, 'public');
        }
        if ($request->hasFile('ogrn_document')) {
            $documents['ogrn_document'] = $request->file('ogrn_document')->store('tenant-documents/'.$tenantId, 'public');
        }
        if ($request->hasFile('director_document')) {
            $documents['director_document'] = $request->file('director_document')->store('tenant-documents/'.$tenantId, 'public');
        }

        $this->tenantOnboarding->verifyDocuments($tenant, $documents);

        $this->log->channel('audit')->$this->logger->info('Tenant documents submitted', [
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'message' => 'Documents submitted for verification',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Approve tenant (admin only)
     * POST /api/v1/tenants/{tenant}/approve
     */
    public function approve(Request $request, string $tenantId): JsonResponse
    {
        // Check if critical changes are blocked due to VPN/Proxy detection
        if ($this->isCriticalChangesBlocked()) {
            return $this->getCriticalChangesBlockedResponse();
        }

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $validated = $request->validate([
            'moderator_notes' => ['nullable', 'string'],
        ]);

        $tenant = Tenant::findOrFail($tenantId);

        $this->tenantOnboarding->approveTenant($tenant, $validated['moderator_notes'] ?? null);

        $this->log->channel('audit')->$this->logger->info('Tenant approved', [
            'tenant_id' => $tenantId,
            'moderator_notes' => $validated['moderator_notes'],
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'message' => 'Tenant approved successfully',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Reject tenant (admin only)
     * POST /api/v1/tenants/{tenant}/reject
     */
    public function reject(Request $request, string $tenantId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $tenant = Tenant::findOrFail($tenantId);

        $this->tenantOnboarding->rejectTenant($tenant, $validated['reason']);

        $this->log->channel('audit')->$this->logger->info('Tenant rejected', [
            'tenant_id' => $tenantId,
            'reason' => $validated['reason'],
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'message' => 'Tenant rejected',
            'correlation_id' => $correlationId,
        ]);
    }
}

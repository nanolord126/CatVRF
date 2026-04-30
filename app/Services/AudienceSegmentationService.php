<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InternalAudience;
use App\Models\User;
use App\Models\Service;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

final readonly class AudienceSegmentationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create audience segment
     */
    public function createAudience(array $data, string $correlationId = ''): InternalAudience
    {
        return $this->db->transaction(function () use ($data, $correlationId) {
            $audience = InternalAudience::create([
                'tenant_id' => $data['tenant_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'segmentation_type' => $data['segmentation_type'] ?? InternalAudience::SEGMENTATION_CUSTOM,
                'segmentation_rules' => $data['segmentation_rules'] ?? null,
                'master_id' => $data['master_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'estimated_size' => 0,
                'is_active' => true,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Calculate estimated size
            $audience->update(['estimated_size' => $this->calculateAudienceSize($audience)]);

            // Invalidate cache
            $this->invalidateAudienceCache($data['tenant_id']);

            $this->logAction(
                'audience_segment_created',
                'InternalAudience',
                $audience->id,
                [
                    'tenant_id' => $data['tenant_id'],
                    'name' => $data['name'],
                    'segmentation_type' => $audience->segmentation_type,
                ],
                $correlationId
            );

            return $audience;
        });
    }

    /**
     * Get audience members
     */
    public function getAudienceMembers(int $audienceId, int $limit = 1000, int $offset = 0): array
    {
        $audience = InternalAudience::findOrFail($audienceId);
        $cacheKey = "audience:members:{$audienceId}:{$limit}:{$offset}";

        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($audience, $limit, $offset) {
            return match ($audience->segmentation_type) {
                InternalAudience::SEGMENTATION_ALL_CLIENTS => $this->getAllClients($audience->tenant_id, $limit, $offset),
                InternalAudience::SEGMENTATION_MASTER_CLIENTS => $this->getMasterClients($audience->master_id, $audience->tenant_id, $limit, $offset),
                InternalAudience::SEGMENTATION_SERVICE_CLIENTS => $this->getServiceClients($audience->service_id, $audience->tenant_id, $limit, $offset),
                InternalAudience::SEGMENTATION_ML_SEGMENT => $this->getMLSegment($audience, $limit, $offset),
                InternalAudience::SEGMENTATION_CUSTOM => $this->getCustomSegment($audience, $limit, $offset),
                default => [],
            };
        });
    }

    /**
     * Get all clients for tenant
     */
    private function getAllClients(int $tenantId, int $limit, int $offset): array
    {
        return User::where('tenant_id', $tenantId)
            ->where('role', 'client')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get clients of specific master
     */
    private function getMasterClients(?int $masterId, int $tenantId, int $limit, int $offset): array
    {
        if (!$masterId) {
            return [];
        }

        // Get clients who have appointments with this master
        return DB::table('appointments')
            ->join('users', 'appointments.user_id', '=', 'users.id')
            ->where('appointments.master_id', $masterId)
            ->where('users.tenant_id', $tenantId)
            ->where('users.role', 'client')
            ->select('users.*')
            ->distinct()
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get clients of specific service
     */
    private function getServiceClients(?int $serviceId, int $tenantId, int $limit, int $offset): array
    {
        if (!$serviceId) {
            return [];
        }

        // Get clients who have appointments for this service
        return DB::table('appointments')
            ->join('users', 'appointments.user_id', '=', 'users.id')
            ->where('appointments.service_id', $serviceId)
            ->where('users.tenant_id', $tenantId)
            ->where('users.role', 'client')
            ->select('users.*')
            ->distinct()
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get ML-based segment
     */
    private function getMLSegment(InternalAudience $audience, int $limit, int $offset): array
    {
        // TODO: Integrate with ML service for segmentation
        // For now, return custom segment
        return $this->getCustomSegment($audience, $limit, $offset);
    }

    /**
     * Get custom segment based on rules
     */
    private function getCustomSegment(InternalAudience $audience, int $limit, int $offset): array
    {
        $rules = $audience->segmentation_rules ?? [];
        $query = User::where('tenant_id', $audience->tenant_id)
            ->where('role', 'client');

        // Apply custom rules
        if (isset($rules['min_orders'])) {
            $query->where('orders_count', '>=', $rules['min_orders']);
        }

        if (isset($rules['min_spent'])) {
            $query->where('total_spent', '>=', $rules['min_spent']);
        }

        if (isset($rules['last_active_days'])) {
            $query->where('last_active_at', '>=', now()->subDays($rules['last_active_days']));
        }

        if (isset($rules['has_subscription'])) {
            $query->where('has_subscription', $rules['has_subscription']);
        }

        return $query->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Calculate audience size
     */
    private function calculateAudienceSize(InternalAudience $audience): int
    {
        return count($this->getAudienceMembers($audience->id, 10000, 0));
    }

    /**
     * Validate audience ownership
     */
    public function validateAudienceOwnership(int $audienceId, int $tenantId): bool
    {
        $audience = InternalAudience::where('id', $audienceId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$audience) {
            return false;
        }

        // Check if audience is restricted to master or service
        if ($audience->isMasterClients() && !$audience->master_id) {
            return false;
        }

        if ($audience->isServiceClients() && !$audience->service_id) {
            return false;
        }

        return true;
    }

    /**
     * Get available masters for segmentation
     */
    public function getAvailableMasters(int $tenantId): array
    {
        return User::where('tenant_id', $tenantId)
            ->where('role', 'master')
            ->select('id', 'name', 'email')
            ->get()
            ->toArray();
    }

    /**
     * Get available services for segmentation
     */
    public function getAvailableServices(int $tenantId): array
    {
        return Service::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('id', 'name', 'category')
            ->get()
            ->toArray();
    }

    private function invalidateAudienceCache(int $tenantId): void
    {
        $this->cache->tags(["audiences:{$tenantId}"])->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use LogManager;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Response;
use Illuminate\Log\LogManager;
use RuntimeException;

/**
 * Tenant Quota Exceeded Exception
 *
 * Production 2026 CANON - Multi-Tenant Resource Enforcement
 *
 * Thrown when a tenant exceeds their allocated quota for any resource.
 * This exception is automatically converted to HTTP 429 Too Many Requests.
 *
 * @author CatVRF Team
 *
 * @version 2026.04.17
 */
final class TenantQuotaExceededException extends RuntimeException
{
    private readonly int $tenantId;

    private readonly string $resourceType;

    private readonly int $used;

    private readonly int $quota;

    private readonly ?int $requested;

    public function __construct(private readonly LogManager $logManager,
        int $tenantId,
        string $resourceType,
        int $used,
        int $quota,
        ?int $requested = null,
        ?\Throwable $previous = null) {
        $this->tenantId = $tenantId;
        $this->resourceType = $resourceType;
        $this->used = $used;
        $this->quota = $quota;
        $this->requested = $requested;

        $message = sprintf(
            'Tenant %d exceeded %s quota: used %d of %d',
            $tenantId,
            $resourceType,
            $used,
            $quota
        );

        if ($requested !== null) {
            $message .= sprintf(', requested %d', $requested);
        }

        parent::__construct($message, 0, $previous);

        // Log for audit and alerting
        $this->logManager /* TODO: inject via constructor DI */ /* TODO: inject via DI */->warning('Tenant quota exceeded', [
            'tenant_id' => $tenantId,
            'resource_type' => $resourceType,
            'used' => $used,
            'quota' => $quota,
            'requested' => $requested,
            'percentage' => $quota > 0 ? round(($used / $quota) * 100, 2) : 0,
        ]);
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getUsed(): int
    {
        return $this->used;
    }

    public function getQuota(): int
    {
        return $this->quota;
    }

    public function getRequested(): ?int
    {
        return $this->requested;
    }

    public function getRemaining(): int
    {
        return max(0, $this->quota - $this->used);
    }

    public function getPercentage(): float
    {
        return $this->quota > 0 ? round(($this->used / $this->quota) * 100, 2) : 0;
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render($request)
    {
        return new JsonResponse([
            'error' => 'quota_exceeded',
            'message' => $this->getMessage(),
            'tenant_id' => $this->tenantId,
            'resource_type' => $this->resourceType,
            'used' => $this->used,
            'quota' => $this->quota,
            'remaining' => $this->getRemaining(),
            'percentage' => $this->getPercentage(),
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
}

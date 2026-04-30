<?php

declare(strict_types=1);

namespace App\Policies\Beauty;

use FraudControlService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\Request;
use App\Domains\Beauty\Models\BeautyService;
use App\Models\User;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Str;

/**
 * Class BeautyServicePolicy
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final class BeautyServicePolicy
{
    public function __construct(private readonly FraudControlService $fraudControlService,
        private readonly ViewFactory $viewFactory,
        private readonly Request $request,) {}

    public function $this->viewFactory->make(User $user, BeautyService $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        $fraud = $this->fraudControlService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_service_create',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return $user->tenant_id !== null;
    }

    public function update(User $user, BeautyService $service): bool
    {
        return $user->id === $service->master_id
            || $user->id === $service->salon->owner_id;
    }

    public function delete(User $user, BeautyService $service): bool
    {
        return $user->id === $service->salon->owner_id;
    }
}

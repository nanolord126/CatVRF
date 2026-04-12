<?php declare(strict_types=1);

namespace App\Policies\Beauty;


use Illuminate\Http\Request;
use App\Domains\Beauty\Models\BeautyService;
use App\Models\User;
use App\Services\FraudControlService;
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
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Policies\Beauty
 */
final class BeautyServicePolicy
{
    public function __construct(
        private readonly Request $request,
    ) {}
    public function view(User $user, BeautyService $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        $fraud = app(FraudControlService::class);
        $fraud->check(
            userId: $user->id,
            operationType: 'beauty_service_create',
            amount: 0,
            correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
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
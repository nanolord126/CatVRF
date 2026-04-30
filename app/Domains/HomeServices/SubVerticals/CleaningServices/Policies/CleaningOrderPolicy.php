<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\SubVerticals\CleaningServices\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Models\User;
use App\Domains\CleaningServices\Models\CleaningOrder;
use App\DTOs\OperationDto;
use Illuminate\Support\Str;

final class CleaningOrderPolicy
{
    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly FraudControlService $fraud,
        private readonly Request $request,) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function $this->viewFactory->make(User $user, CleaningOrder $cleaningOrder): bool
    {
        return $user->tenant_id === $cleaningOrder->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $this->fraud->check(new OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString()));

        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CleaningOrder $cleaningOrder): bool
    {
        $this->fraud->check(new OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString()));

        return $user->tenant_id === $cleaningOrder->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CleaningOrder $cleaningOrder): bool
    {
        $this->fraud->check(new OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString()));

        return $user->tenant_id === $cleaningOrder->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CleaningOrder $cleaningOrder): bool
    {
        return $user->tenant_id === $cleaningOrder->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CleaningOrder $cleaningOrder): bool
    {
        return false;
    }
}

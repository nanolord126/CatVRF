<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\DTOs\OperationDto;
use Illuminate\Support\Str;

final class CourierServicePolicy
{
    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly FraudControlService $fraud,
        private readonly Request $request,) {}


    // Dependencies injected via constructor
    // Add private readonly properties here
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, CourierService $courierService): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        $this->fraud->check(new OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString()));

        return $user->hasPermissionTo('create_courier_service') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, CourierService $courierService): Response
    {
        $this->fraud->check(new OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString()));

        return $user->id === $courierService->user_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, CourierService $courierService): Response
    {
        $this->fraud->check(new OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString()));

        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}

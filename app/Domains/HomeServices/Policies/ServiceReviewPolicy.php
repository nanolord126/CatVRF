<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;


use Illuminate\Http\Request;
use Carbon\Carbon;



use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
final class ServiceReviewPolicy
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly Guard $guard,
        private readonly Request $request,) {}


    // Dependencies injected via constructor
        // Add private readonly properties here
        public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, ServiceReview $review): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()));

            return $user->$this->guard ? $this->response->allow() : $this->response->deny('Unauthorized');
        }

        public function update(User $user, ServiceReview $review): Response
        {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()));

            return $user->id === $review->reviewer_id || $user->hasPermissionTo('update_reviews') ? $this->response->allow() : $this->response->deny('Unauthorized');
        }

        public function delete(User $user, ServiceReview $review): Response
        {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()));

            return $user->id === $review->reviewer_id || $user->hasPermissionTo('delete_reviews') ? $this->response->allow() : $this->response->deny('Unauthorized');
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}

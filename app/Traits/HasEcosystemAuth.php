<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Contracts\Auth\Factory as AuthFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasEcosystemAuth
{
    public function __construct(
        private readonly AuthFactory $authFactory,
    ) {}

    public function ecosystemUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function isOwnedByCurrentUser(): bool
    {
        return $this->isOwnedBy($this->authFactory->id());
    }
}

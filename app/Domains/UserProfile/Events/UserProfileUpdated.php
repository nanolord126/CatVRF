<?php declare(strict_types=1);

namespace App\Domains\UserProfile\Events;

use App\Domains\UserProfile\Models\UserProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserProfileUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserProfile $profile,
        public readonly string $correlationId,
    ) {}
}

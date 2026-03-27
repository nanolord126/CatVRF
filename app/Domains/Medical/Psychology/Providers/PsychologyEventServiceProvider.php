<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Providers;

use App\Domains\Medical\Psychology\Events\PsychologicalBookingCreated;
use App\Domains\Medical\Psychology\Listeners\HandlePsychologicalBookingCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Провайдер событий для вертикали Психологии.
 */
final class PsychologyEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PsychologicalBookingCreated::class => [
            HandlePsychologicalBookingCreated::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}

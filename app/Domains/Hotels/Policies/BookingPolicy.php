<?php declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\Booking;
use Illuminate\Auth\Access\Response;

final class BookingPolicy
{
    public function viewAny(): Response
    {
        return auth()->check()
            ? Response::allow()
            : Response::deny('Not authorized');
    }

    public function view(): Response
    {
        return auth()->check()
            ? Response::allow()
            : Response::deny('Not authorized');
    }

    public function create(): Response
    {
        return auth()->check()
            ? Response::allow()
            : Response::deny('Not authorized');
    }

    public function cancel(): Response
    {
        return auth()->check() && (auth()->user()->is_admin || auth()->user()->is_guest)
            ? Response::allow()
            : Response::deny('Not authorized');
    }

    public function delete(): Response
    {
        return auth()->check() && auth()->user()->is_admin
            ? Response::allow()
            : Response::deny('Not authorized');
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\PricingRule;
use Illuminate\Auth\Access\Response;

final class PricingRulePolicy
{
    public function viewAny(): Response
    {
        return auth()->check() && (auth()->user()->is_admin || auth()->user()->is_hotel_owner)
            ? Response::allow()
            : Response::deny('Not authorized');
    }

    public function view(): Response
    {
        return auth()->check() && (auth()->user()->is_admin || auth()->user()->is_hotel_owner)
            ? Response::allow()
            : Response::deny('Not authorized');
    }

    public function create(): Response
    {
        return auth()->check() && (auth()->user()->is_admin || auth()->user()->is_hotel_owner)
            ? Response::allow()
            : Response::deny('Not authorized');
    }

    public function update(): Response
    {
        return auth()->check() && (auth()->user()->is_admin || auth()->user()->is_hotel_owner)
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

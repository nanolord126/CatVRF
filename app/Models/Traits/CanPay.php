<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanPay
{
    public function payments(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->where('status', 'captured')->sum('amount');
    }
}

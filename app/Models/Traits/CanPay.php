<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanPay
{
    public function payments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->where('status', 'captured')->sum('amount');
    }
}

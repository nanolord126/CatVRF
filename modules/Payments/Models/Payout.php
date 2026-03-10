<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'amount',
        'tax_amount',
        'net_amount',
        'contract_type',
        'status',
        'notes',
    ];

    public static function createWithTax(float $amount, string $contractType = 'standard'): self
    {
        $tax = 0;
        $net = $amount;
        $notes = '';

        if ($contractType === 'gph') {
            $tax = round($amount * 0.13, 2);
            $net = $amount - $tax;
            $notes = "NDFL 13% deducted. Other social contributions are the responsibility of the individual.";
        }

        return self::create([
            'amount' => $amount,
            'tax_amount' => $tax,
            'net_amount' => $net,
            'contract_type' => $contractType,
            'notes' => $notes,
        ]);
    }
}

<?php

declare(strict_types=1);


namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final /**
 * PharmacyOrderItem
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PharmacyOrderItem extends Model
{
    protected $table = 'pharmacy_order_items';

    protected $fillable = [
        'order_id',
        'medication_id',
        'quantity',
        'price_at_order',
        'correlation_id'
    ];

    protected $casts = [
        'price_at_order' => 'integer',
        'quantity' => 'integer'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PharmacyOrder::class, 'order_id');
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }
}
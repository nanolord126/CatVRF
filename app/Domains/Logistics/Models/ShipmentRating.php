<?php

declare(strict_types=1);


namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * ShipmentRating
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentRating extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shipment_ratings';

    protected $fillable = [
        'tenant_id',
        'shipment_id',
        'reviewer_id',
        'rating',
        'comment',
        'media',
        'verified_purchase',
        'correlation_id',
    ];

    protected $casts = [
        'media' => 'collection',
        'verified_purchase' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

declare(strict_types=1);

<?php

namespace App\Models\Domains\Delivery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * DeliveryOrder
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeliveryOrder extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\DeliveryOrderFactory::new();
    }


    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
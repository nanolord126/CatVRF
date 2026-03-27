<?php

declare(strict_types=1);
namespace App\Models\Domains\RealEstate; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Factories\HasFactory; /**
 * Property
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Property extends Model { use HasFactory; protected $guarded = []; protected static function newFactory() { return \Database\Factories\PropertyFactory::new(); } 
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

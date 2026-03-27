<?php

declare(strict_types=1);
namespace App\Models\Domains\Beauty; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Factories\HasFactory; /**
 * Salon
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Salon extends Model { use HasFactory; protected $table = "beauty_salons"; protected $guarded = []; protected static function newFactory() { return \Database\Factories\SalonFactory::new(); } 
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

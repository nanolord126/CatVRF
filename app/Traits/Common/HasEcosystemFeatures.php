<?php

namespace App\Traits\Common;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Глобальный Trait для Production-Ready моделей 2026.
 * Объединяет Аудит, Soft Deletes и сквозную корреляцию.
 */
trait HasEcosystemFeatures
{
    use SoftDeletes;

    public static function bootHasEcosystemFeatures()
    {
        static::creating(function ($model) {
            // Автоматическая генерация correlation_id если его нет
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope для автоматической фильтрации только активных (не на ремонте/уборке) сущностей.
     */
    public function scopeActive(Builder $query): Builder
    {
        if (in_array(get_class($this), ['App\Models\Hotel\HotelRoom', 'App\Models\Taxi\TaxiVehicle'])) {
            return $query->where('status', 'available');
        }
        
        return $query;
    }
}

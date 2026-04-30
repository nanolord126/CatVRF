<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * FurnitureRoomType Model
 */
final class FurnitureRoomType extends Model
{
    protected $table = 'furniture_room_types';

    protected $fillable = ['tenant_id', 'uuid', 'name', 'slug', 'style_presets'];

    protected $casts = [
        'style_presets' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });

        self::creating(fn ($model) => $model->uuid = (string) Str::uuid());
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}

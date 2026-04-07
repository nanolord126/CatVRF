<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * FurnitureRoomType Model
     */
final class FurnitureRoomType extends Model
{
        protected $table = 'furniture_room_types';

        protected $fillable = ['uuid', 'name', 'slug', 'style_presets'];

        protected $casts = [
            'style_presets' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(fn ($model) => $model->uuid = (string) Str::uuid());
        }
    }

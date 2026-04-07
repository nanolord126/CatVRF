<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Master extends Model
{
    protected $table = 'beauty_masters';

    protected $fillable = [
        'salon_id',
        'user_id',
        'uuid',
        'correlation_id',
        'full_name',
        'specialization',
        'rating',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'json',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'master_id');
    }
}

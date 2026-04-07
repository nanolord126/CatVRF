<?php declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class PhotoStudio extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'photography_studios';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'address',
        'geo_point',
        'schedule_json',
        'amenities',
        'is_active',
        'correlation_id',
        'tags'
    ];

    protected $casts = [
        'uuid' => 'string',
        'schedule_json' => 'json',
        'amenities' => 'json',
        'is_active' => 'boolean',
        'tags' => 'json',
        'rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->tenant_id ??= tenant()?->id;
            $model->correlation_id ??= $this->request->header('X-Correlation-ID', (string) Str::uuid());
        });
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'studio_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'studio_id');
    }
}

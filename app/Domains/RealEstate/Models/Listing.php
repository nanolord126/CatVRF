<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

final class Listing extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'real_estate_listings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'property_id',
        'correlation_id',
        'deal_type',
        'price',
        'deposit',
        'commission_percent',
        'is_b2b',
        'rules',
        'status',
        'published_at',
        'tags',
    ];

    protected $casts = [
        'price' => 'integer',
        'deposit' => 'integer',
        'commission_percent' => 'integer',
        'is_b2b' => 'boolean',
        'rules' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Listing $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });
    }

    public function property(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    public function b2bDeals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(B2BDeal::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('real_estate_listings')
            ->logOnlyDirty();
    }
}

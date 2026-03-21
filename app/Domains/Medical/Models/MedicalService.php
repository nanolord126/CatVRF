<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MedicalService extends Model
{
    use SoftDeletes;

    protected $table = 'medical_services';

    protected $fillable = [
        'tenant_id',
        'clinic_id',
        'name',
        'description',
        'category',
        'price',
        'cost_price',
        'duration_minutes',
        'requires_tests',
        'tags',
        'is_active',
        'rating',
        'review_count',
        'correlation_id',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'requires_tests' => 'collection',
        'tags' => 'collection',
        'price' => 'float',
        'cost_price' => 'float',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = auth()?->user()?->tenant_id ?? filament()?->getTenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(MedicalClinic::class, 'clinic_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(MedicalAppointment::class, 'service_id');
    }
}

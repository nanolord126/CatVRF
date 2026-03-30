<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceListing extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'service_listings';


        protected $fillable = ['tenant_id', 'contractor_id', 'category_id', 'name', 'description', 'type', 'base_price', 'estimated_duration_minutes', 'equipment', 'requirements', 'rating', 'booking_count', 'completion_count', 'is_active', 'correlation_id'];


        protected $hidden = [];


        protected $casts = ['equipment' => 'collection', 'requirements' => 'collection', 'base_price' => 'float', 'rating' => 'float', 'is_active' => 'boolean'];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));


        }


        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }


        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }


        public function category(): BelongsTo { return $this->belongsTo(ServiceCategory::class); }


        public function jobs(): HasMany { return $this->hasMany(ServiceJob::class); }


        public function reviews(): HasMany { return $this->hasMany(ServiceReview::class); }
}

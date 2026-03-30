<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'service_reviews';
        protected $fillable = ['tenant_id', 'service_listing_id', 'contractor_id', 'job_id', 'reviewer_id', 'rating', 'title', 'content', 'categories', 'helpful_count', 'unhelpful_count', 'verified_job', 'published_at', 'correlation_id'];
        protected $hidden = [];
        protected $casts = ['categories' => 'collection', 'verified_job' => 'boolean', 'published_at' => 'datetime'];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));
        }

        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
        public function serviceListing(): BelongsTo { return $this->belongsTo(ServiceListing::class); }
        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
        public function job(): BelongsTo { return $this->belongsTo(ServiceJob::class); }
        public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
}

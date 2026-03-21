<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServiceJob extends Model
{
    protected $table = 'service_jobs';
    protected $fillable = ['tenant_id', 'service_listing_id', 'contractor_id', 'client_id', 'status', 'description', 'address_point', 'address', 'scheduled_at', 'started_at', 'completed_at', 'actual_duration_minutes', 'base_amount', 'commission_amount', 'total_amount', 'payment_status', 'transaction_id', 'photos', 'notes', 'correlation_id'];
    protected $hidden = [];
    protected $casts = ['photos' => 'collection', 'base_amount' => 'float', 'commission_amount' => 'float', 'total_amount' => 'float', 'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function serviceListing(): BelongsTo { return $this->belongsTo(ServiceListing::class); }
    public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
    public function client(): BelongsTo { return $this->belongsTo(User::class, 'client_id'); }
    public function reviews(): HasMany { return $this->hasMany(ServiceReview::class, 'job_id'); }
    public function disputes(): HasMany { return $this->hasMany(ServiceDispute::class, 'job_id'); }
}

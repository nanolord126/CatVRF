<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceDispute extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'service_disputes';


        protected $fillable = ['tenant_id', 'job_id', 'initiator_id', 'type', 'description', 'status', 'resolution', 'resolved_by', 'refund_amount', 'evidence', 'resolved_at', 'correlation_id'];


        protected $hidden = [];


        protected $casts = ['evidence' => 'collection', 'refund_amount' => 'float', 'resolved_at' => 'datetime'];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));


        }


        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }


        public function job(): BelongsTo { return $this->belongsTo(ServiceJob::class); }


        public function initiator(): BelongsTo { return $this->belongsTo(User::class, 'initiator_id'); }
}

<?php declare(strict_types=1);

namespace App\Models\Consulting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class ConsultingContract extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'consulting_contracts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consulting_project_id',
            'consultant_id',
            'consulting_firm_id',
            'client_id',
            'contract_number',
            'status', // 'draft', 'signed', 'expired', 'terminated'
            'started_at',
            'ended_at',
            'total_amount',
            'signed_at',
            'terms',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'consulting_project_id' => 'integer',
            'consultant_id' => 'integer',
            'consulting_firm_id' => 'integer',
            'client_id' => 'integer',
            'tags' => 'json',
            'terms' => 'json',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'signed_at' => 'datetime',
            'total_amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = [
            'deleted_at',
        ];

        /**
         * Boot logic for multi-tenancy and consistent UUID generation.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relationships.
         */
        public function project(): BelongsTo
        {
            return $this->belongsTo(ConsultingProject::class, 'consulting_project_id');
        }

        public function consultant(): BelongsTo
        {
            return $this->belongsTo(Consultant::class, 'consultant_id');
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        public function firm(): BelongsTo
        {
            return $this->belongsTo(ConsultingFirm::class, 'consulting_firm_id');
        }

        /**
         * Scopes.
         */
        public function scopeSigned(Builder $query): Builder
        {
            return $query->where('status', 'signed');
        }

        public function scopeDraft(Builder $query): Builder
        {
            return $query->where('status', 'draft');
        }

        /**
         * Domain Methods.
         */
        public function isSigned(): bool
        {
            return $this->status === 'signed';
        }

        public function isExpired(): bool
        {
            return $this->status === 'expired' || (isset($this->ended_at) && $this->ended_at->isPast());
        }

        public function getFormattedAmount(): string
        {
            return number_format($this->total_amount / 100, 2) . ' RUB';
        }

        public function getContractDurationInDays(): int
        {
            if (!$this->started_at || !$this->ended_at) return 0;
            return (int) $this->started_at->diffInDays($this->ended_at);
        }

        public function getContractSummary(): string
        {
            return "Contract #{$this->contract_number} | Amount: " . $this->getFormattedAmount() . " | Status: " . ucfirst($this->status);
        }

        public function markAsSigned(string $userId): void
        {
            $this->update([
               'status' => 'signed',
               'signed_at' => now(),
               'correlation_id' => (string) Str::uuid(),
            ]);

            $currentTerms = $this->terms ?? [];
            $currentTerms[] = ['action' => 'signed', 'by_user' => $userId, 'timestamp' => now()->toIso8601String()];
            $this->update(['terms' => $currentTerms]);
        }
}

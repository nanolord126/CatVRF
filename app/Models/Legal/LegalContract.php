<?php declare(strict_types=1);

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Illuminate\Support\Str;

final class LegalContract extends Model
{

        protected $table = 'legal_contracts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consultation_id',
            'client_id',
            'title',
            'content',
            'status',
            'signed_at',
            'digital_signature',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'consultation_id' => 'integer',
            'client_id' => 'integer',
            'status' => 'string', // draft, signed, completed, archived
            'signed_at' => 'datetime',
            'digital_signature' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant')) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(User::class, 'client_id');
        }

        public function consultation(): BelongsTo
        {
            return $this->belongsTo(LegalConsultation::class, 'consultation_id');
        }

        public function scopeSigned(Builder $query): Builder
        {
            return $query->where('status', 'signed');
        }

        public function scopeInDraft(Builder $query): Builder
        {
            return $query->where('status', 'draft');
        }

        public function isSigned(): bool
        {
            return !empty($this->signed_at) || $this->status === 'signed';
        }

        public function isFinalized(): bool
        {
            return in_array($this->status, ['signed', 'completed', 'archived']);
        }

        public function markAsSigned(array $signature): void
        {
            $this->update([
                'status' => 'signed',
                'signed_at' => now(),
                'digital_signature' => $signature,
            ]);
        }

        public function contentPreview(int $length = 100): string
        {
            return Str::limit(strip_tags($this->content), $length);
        }
}

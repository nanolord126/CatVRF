<?php declare(strict_types=1);

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class LegalService extends Model
{

        protected $table = 'legal_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'description',
            'base_price',
            'type',
            'metadata',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'base_price' => 'integer', // cents
            'metadata' => 'json',
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

        public function contracts(): HasMany
        {
            return $this->hasMany(LegalContract::class, 'service_id');
        }

        public function scopeByType(Builder $query, string $type): Builder
        {
            return $query->where('type', $type);
        }

        public function getFormattedPrice(): string
        {
            return number_format((float) ($this->base_price / 100), 2, '.', ' ') . ' ₽';
        }

        public function isHighValueService(): bool
        {
            return $this->base_price > 1000000; // 10,000 RUB
        }

        public function getDescriptionForB2B(): string
        {
            return "B2B Contract Service: " . ($this->name ?? 'Standard');
        }

        public function hasConsultationIncluded(): bool
        {
            return str_contains(strtolower($this->description), 'консультация');
        }

        public function getServiceTypeRussianLabel(): string
        {
            return match ($this->type) {
                'representation' => 'Представительство',
                'consultation' => 'Консультация',
                'notary' => 'Нотариальные услуги',
                default => 'Другое',
            };
        }
}

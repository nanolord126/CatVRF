<?php declare(strict_types=1);

namespace App\Models\Consulting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'consulting_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consultant_id',
            'consulting_firm_id',
            'name',
            'description',
            'type', // 'hourly', 'fixed_project', 'subscription'
            'duration_minutes',
            'price',
            'is_available',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'consultant_id' => 'integer',
            'consulting_firm_id' => 'integer',
            'tags' => 'json',
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'is_available' => 'boolean',
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
        public function consultant(): BelongsTo
        {
            return $this->belongsTo(Consultant::class, 'consultant_id');
        }

        public function firm(): BelongsTo
        {
            return $this->belongsTo(ConsultingFirm::class, 'consulting_firm_id');
        }

        public function sessions(): HasMany
        {
            return $this->hasMany(ConsultingSession::class, 'consulting_service_id');
        }

        /**
         * Scopes.
         */
        public function scopeAvailable(Builder $query): Builder
        {
            return $query->where('is_available', true);
        }

        public function scopeByType(Builder $query, string $type): Builder
        {
            return $query->where('type', $type);
        }

        /**
         * Domain Methods.
         */
        public function getFormattedPrice(): string
        {
            $base = number_format($this->price / 100, 2) . ' RUB';
            return match($this->type) {
                'hourly' => $base . ' / hr',
                'fixed_project' => $base . ' (fixed)',
                'subscription' => $base . ' / mo',
                default => $base,
            };
        }

        public function isSubscription(): bool
        {
            return $this->type === 'subscription';
        }

        public function isHourly(): bool
        {
            return $this->type === 'hourly';
        }

        public function isFixedProject(): bool
        {
            return $this->type === 'fixed_project';
        }

        public function getTotalSessionMinutes(): int
        {
            return $this->sessions()->sum('duration_minutes');
        }

        public function getRevenueGenerated(): int
        {
            return (int) $this->sessions()->where('payment_status', 'paid')->sum('price');
        }

        public function getServiceSummary(): string
        {
            return "Service: {$this->name} | Type: " . ucfirst($this->type) . " | Price: " . $this->getFormattedPrice();
        }
}

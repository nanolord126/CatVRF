<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventVendor extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $table = 'event_planning_vendors';

        protected $fillable = [
            'uuid',
            'event_id',
            'tenant_id',
            'vertical',
            'vendor_id',
            'vendor_name',
            'status',
            'agreed_price_kopecks',
            'deposit_paid_kopecks',
            'agreed_conditions',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'agreed_price_kopecks' => 'integer',
            'deposit_paid_kopecks' => 'integer',
            'agreed_conditions' => 'array',
        ];

        /**
         * Booted method with Global Scopes.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id)) {
                    $model->tenant_id = tenant()->id ?? auth()->user()?->current_tenant_id ?? 1;
                }
            });

            static::addGlobalScope('tenant', function ($query) {
                if ($tenant = tenant()) {
                    $query->where('tenant_id', $tenant->id);
                }
            });
        }

        /**
         * Relations.
         */
        public function event(): BelongsTo
        {
            return $this->belongsTo(Event::class, 'event_id');
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        /**
         * Helpers.
         */
        public function isPending(): bool
        {
            return $this->status === 'pending';
        }

        public function isApproved(): bool
        {
            return $this->status === 'approved';
        }

        public function isContracted(): bool
        {
            return $this->status === 'contracted';
        }

        public function isPaid(): bool
        {
            return $this->status === 'paid';
        }

        public function getPriceInRublesAttribute(): float
        {
            return $this->agreed_price_kopecks / 100;
        }

        public function getDepositInRublesAttribute(): float
        {
            return $this->deposit_paid_kopecks / 100;
        }

        /**
         * Маркировка вертикали для UI/Business Logic.
         */
        public function getVerticalLabelAttribute(): string
        {
            return match ($this->vertical) {
                'food' => 'Рестораны и Кейтеринг',
                'photo' => 'Фотография и Видео',
                'beauty' => 'Красота и Стилисты',
                'auto' => 'Транспорт и Логистика',
                'decoration' => 'Декор и Оформление',
                'music' => 'Музыка и Развлечения',
                default => 'Другое',
            };
        }
}

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceOrder extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $table = 'freelance_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'client_id',
            'freelancer_id',
            'offer_id',
            'title',
            'requirements',
            'budget_kopecks',
            'commission_kopecks',
            'status',
            'deadline_at',
            'completed_at',
            'is_b2b',
            'business_group_id',
            'correlation_id',
        ];

        protected $casts = [
            'budget_kopecks' => 'integer',
            'commission_kopecks' => 'integer',
            'is_b2b' => 'boolean',
            'deadline_at' => 'datetime',
            'completed_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', tenant()->id ?? 1);
            });
        }

        /**
         * Клиент заказа.
         */
        public function client(): BelongsTo
        {
            return $this->belongsTo(User::class, 'client_id');
        }

        /**
         * Исполнитель (фрилансер).
         */
        public function freelancer(): BelongsTo
        {
            return $this->belongsTo(Freelancer::class, 'freelancer_id');
        }

        /**
         * Ссылка на предложение услуги (если заказ по предложению).
         */
        public function offer(): BelongsTo
        {
            return $this->belongsTo(FreelanceServiceOffer::class, 'offer_id');
        }

        /**
         * Связанный контракт (эскроу).
         */
        public function contract(): BelongsTo
        {
            return $this->hasOne(FreelanceContract::class, 'order_id')->withDefault();
        }

        /**
         * Отзыв по заказу.
         */
        public function review(): BelongsTo
        {
            return $this->hasOne(FreelanceReview::class, 'order_id');
        }
}

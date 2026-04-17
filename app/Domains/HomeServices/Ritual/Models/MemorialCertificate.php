<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Ritual\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MemorialCertificate extends Model
{


        protected $table = 'ritual_certificates';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'client_id',
            'code',
            'type',
            'nominal_kopecks',
            'is_redeemed',
            'redeemed_at',
            'expires_at',
            'metadata',
            'correlation_id',
        ];

        protected $hidden = [
            'id',
            'deleted_at',
        ];

        protected $casts = [
            'is_redeemed' => 'boolean',
            'nominal_kopecks' => 'integer',
            'redeemed_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'json',
            'tenant_id' => 'integer',
        ];

        /**
         * Booted method for global scoping and logic hooks.
         */
        protected static function booted_disabled(): void
        {
            // Изоляция данных на уровне базы (Tenant Scoping)
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()->id) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });

            // Автогенерация UUID, Code и Correlation ID
            static::creating(function (MemorialCertificate $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (empty($model->code)) {
                    $model->code = 'RIT-' . strtoupper(Str::random(8));
                }
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = (int) tenant()->id;
                }
            });
        }

        /**
         * Владелец сертификата.
         */
        public function client(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        /**
         * Проверка валидности сертификата.
         */
        public function isValid(): bool
        {
            return !$this->is_redeemed &&
                   ($this->expires_at === null || $this->expires_at->isFuture());
        }
}

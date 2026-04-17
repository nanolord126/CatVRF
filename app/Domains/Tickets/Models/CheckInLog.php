<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class CheckInLog extends Model
{


        protected $table = 'check_in_logs';

        protected $fillable = [
            'uuid', 'tenant_id', 'ticket_id', 'checker_user_id',
            'ip_address', 'device_info', 'is_success',
            'error_reason', 'location', 'correlation_id'
        ];

        protected $casts = [
            'is_success' => 'boolean',
            'location' => 'json',
            'device_info' => 'json'
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()?->id) {
                    $builder->where('tenant_id', tenant()?->id);
                }
            });

            static::creating(function ($model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()?->id;
                }
            });
        }

        

        /**
         * Билет лога.
         */
        public function ticket(): BelongsTo
        {
            return $this->belongsTo(Ticket::class);
        }

        /**
         * Кто проверял.
         */
        public function checker(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'checker_user_id');
        }
}

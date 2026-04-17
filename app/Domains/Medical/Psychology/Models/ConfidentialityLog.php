<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConfidentialityLog extends Model
{


    protected $table = 'psy_confidentiality_logs';

        public $timestamps = false; // Юзаем только created_at по дефолту

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'user_id',
            'session_id',
            'action',
            'ip_address',
            'reason',
            'correlation_id',
            'created_at',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });

            static::creating(function (self $model) {
                $model->correlation_id = (string) Str::uuid();
                $model->tenant_id = tenant()->id ?? 0;
                $model->ip_address = $this->request->ip();
                $model->created_at = now();
            });
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function session(): BelongsTo
        {
            return $this->belongsTo(PsychologicalSession::class, 'session_id');
        }
}

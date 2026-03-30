<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConfidentialityLog extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'psy_confidentiality_logs';

        public $timestamps = false; // Юзаем только created_at по дефолту

        protected $fillable = [
            'tenant_id',
            'user_id',
            'session_id',
            'action',
            'ip_address',
            'reason',
            'correlation_id',
            'created_at',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });

            static::creating(function (self $model) {
                $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
                $model->tenant_id = auth()->user()->tenant_id ?? 0;
                $model->ip_address = request()->ip();
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

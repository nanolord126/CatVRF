<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PsychologicalReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'psy_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'psychologist_id',
            'rating',
            'comment',
            'is_public',
            'correlation_id',
        ];

        protected $casts = [
            'rating' => 'integer',
            'is_public' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });

            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
                $model->tenant_id = auth()->user()->tenant_id ?? 0;
            });
        }

        public function psychologist(): BelongsTo
        {
            return $this->belongsTo(Psychologist::class, 'psychologist_id');
        }
}

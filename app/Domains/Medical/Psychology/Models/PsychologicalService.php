<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PsychologicalService extends Model
{

    use HasFactory;

    protected $table = 'psy_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'psychologist_id',
            'name',
            'duration_minutes',
            'price',
            'delivery_type',
            'description',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'price' => 'integer',
            'duration_minutes' => 'integer',
            'tags' => 'json',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });

            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = (string) Str::uuid();
                $model->tenant_id = tenant()->id ?? 0;
            });
        }

        public function psychologist(): BelongsTo
        {
            return $this->belongsTo(Psychologist::class, 'psychologist_id');
        }
}

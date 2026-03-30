<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Prescription extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'prescriptions';


        protected $fillable = [


            'tenant_id',


            'user_id',


            'doctor_id',


            'uuid',


            'prescription_number',


            'expires_at',


            'status',


            'ocr_data',


            'scan_path',


            'tags',


            'correlation_id'


        ];


        protected $casts = [


            'expires_at' => 'date',


            'tags' => 'json'


        ];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_id', function (Builder $builder) {


                $builder->where('tenant_id', tenant()->id ?? 0);


            });


            static::creating(function (Model $model) {


                $model->uuid = $model->uuid ?? (string) Str::uuid();


                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);


            });


        }


        public function user(): BelongsTo


        {


            return $this->belongsTo(User::class, 'user_id');


        }
}

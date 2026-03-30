<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HomeServiceJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'home_service_jobs';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'contractor_id',
            'correlation_id',
            'status',
            'service_date',
            'price',
            'tags',
            'meta',
        ];

        protected $casts = [
            'service_date' => 'datetime',
            'tags' => 'json',
            'meta' => 'json',
            'price' => 'integer',
        ];
    }


    use Illuminate\Database\Eloquent\Model;


    use Illuminate\Database\Eloquent\SoftDeletes;


    use App\Traits\TenantScoped;


    final class HomeServiceJob extends Model


    {


        use SoftDeletes, TenantScoped;


        protected $table = 'home_service_jobs';


        protected $fillable = [


            'tenant_id', 'uuid', 'correlation_id',


            'contractor_id', 'client_id', 'service_type', 'datetime',


            'address', 'status', 'price', 'tags', 'meta'


        ];


        protected $casts = [


            'price' => 'int',


            'tags' => 'json',


            'meta' => 'json',


        ];


        protected static function booted(): void


        {


            parent::booted();


            static::addGlobalScope('tenant_id', function ($query) {


                if (function_exists('tenant') && tenant('id')) {


                    $query->where('tenant_id', tenant('id'));


                }


            });


        }
}

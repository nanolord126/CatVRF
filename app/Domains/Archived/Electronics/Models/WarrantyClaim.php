<?php declare(strict_types=1);

namespace App\Domains\Archived\Electronics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WarrantyClaim extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'warranty_claims';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'product_id',
            'correlation_id',
            'status',
            'description',
            'tags',
            'meta',
        ];

        protected $casts = [
            'tags' => 'json',
            'meta' => 'json',
        ];
    }


    use Illuminate\Database\Eloquent\Concerns\HasUuids;


    use Illuminate\Database\Eloquent\Factories\HasFactory;


    use Illuminate\Database\Eloquent\Model;


    use Illuminate\Database\Eloquent\Relations\BelongsTo;


    use Illuminate\Database\Eloquent\SoftDeletes;


    use App\Traits\TenantScoped;


    final class WarrantyClaim extends Model


    {


        use HasFactory, HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'warranty_claims';


        protected $fillable = [


            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',


            'product_id', 'client_id', 'issue_description', 'claim_date',


            'resolution_date', 'status', 'resolution_notes', 'tags',


        ];


        protected $casts = [


            'claim_date'       => 'datetime',


            'resolution_date'  => 'datetime',


            'tags'             => 'json',


        ];


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function product(): BelongsTo


        {


            return $this->belongsTo(ElectronicProduct::class, 'product_id');


        }


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function isPending(): bool


        {


            return $this->status === 'pending';


        }


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function isResolved(): bool


        {


            return $this->status === 'resolved';


        }


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

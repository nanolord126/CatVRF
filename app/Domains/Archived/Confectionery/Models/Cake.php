<?php declare(strict_types=1);

namespace App\Domains\Archived\Confectionery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Cake extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes, TenantScoped;


        protected $table = "cakes";


        protected $guarded = [];


        protected static function newFactory()


        {


            return CakeFactory::new();


        }


        protected static function booted(): void


        {


            parent::booted();


            static::addGlobalScope("tenant_id", function ($query) {


                if (function_exists("tenant") && tenant("id")) {


                    $query->where("tenant_id", tenant("id"));


                }


            });


        }
}

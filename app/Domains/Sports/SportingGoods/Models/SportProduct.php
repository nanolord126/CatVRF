<?php declare(strict_types=1);

namespace App\Domains\Sports\SportingGoods\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SportProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids;

        protected $table = "sport_products";

        protected $fillable = [
            "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
            "name", "price"
        ];

        protected $casts = [
            "tags" => "json",
            "price" => "integer",
        ];

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

declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final /**
 * GiftProduct
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GiftProduct extends Model
{
    use HasFactory, HasUuids;

    protected $table = "gift_products";

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

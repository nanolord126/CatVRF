<?php
declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

final class Dish extends Model
{
    protected $table = "food_dishes";

    protected $fillable = [
        "restaurant_id", "uuid", "correlation_id",
        "name", "description", "price", "weight_grams",
        "calories", "proteins", "fats", "carbohydrates",
        "is_available", "modifiers", "image_url"
    ];

    protected $casts = [
        "modifiers" => "json",
        "is_available" => "boolean",
        "price" => "decimal:2",
        "weight_grams" => "integer",
        "calories" => "integer",
        "proteins" => "decimal:2",
        "fats" => "decimal:2",
        "carbohydrates" => "decimal:2",
    ];

    protected static function booted(): void
    {
        static::creating(function (Model $model): void {
            if (!$model->uuid) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, "restaurant_id");
    }
}

<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class SportsNutritionDomainTrait extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()->id ?? 0;
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = $this->request->header('X-Correlation-ID', (string) Str::uuid());
            }
        });
    }
}

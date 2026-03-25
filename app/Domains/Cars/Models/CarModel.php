declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Cars\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final /**
 * CarModel
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CarModel extends Model
{
    protected $table = 'car_models';

    protected $fillable = [
        'brand_id',
        'uuid',
        'name',
        'slug',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'tags' => 'json'
    ];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'brand_id');
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'model_id');
    }
}
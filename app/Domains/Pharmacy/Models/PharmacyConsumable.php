declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final /**
 * PharmacyConsumable
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PharmacyConsumable extends Model
{
    protected $table = 'pharmacy_consumables';
    protected $fillable = ['uuid', 'tenant_id', 'pharmacy_id', 'name', 'stock', 'min_threshold', 'correlation_id', 'tags'];
    protected $casts = ['tags' => 'json'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
        static::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));
    }
}

declare(strict_types=1);

<?php

namespace Modules\Bonuses\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BonusProgram
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BonusProgram extends Model
{
    protected $fillable = [
        'name',
        'type', // cashback, fixed, discount
        'value',
        'is_active',
        'owner_id',
        'owner_type',
    ];

    public function owner()
    {
        return $this->morphTo();
    }
}

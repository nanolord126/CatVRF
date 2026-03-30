<?php declare(strict_types=1);

namespace Modules\Bonuses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BonusProgram extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

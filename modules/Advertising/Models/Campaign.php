<?php declare(strict_types=1);

namespace Modules\Advertising\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Campaign extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'ad_campaigns';
        protected $fillable = ['tenant_id', 'name', 'budget', 'vertical', 'is_active', 'start_date', 'end_date', 'erid'];
        protected $casts = ['is_active' => 'boolean', 'budget' => 'decimal:2'];
    
        public function creatives(): HasMany {
            return $this->hasMany(Creative::class);
        }
}

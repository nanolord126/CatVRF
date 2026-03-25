declare(strict_types=1);

<?php
namespace Modules\Advertising\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant;

/**
 * Campaign
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Campaign extends Model {
    protected $table = 'ad_campaigns';
    protected $fillable = ['tenant_id', 'name', 'budget', 'vertical', 'is_active', 'start_date', 'end_date', 'erid'];
    protected $casts = ['is_active' => 'boolean', 'budget' => 'decimal:2'];

    public function creatives(): HasMany {
        return $this->hasMany(Creative::class);
    }
}

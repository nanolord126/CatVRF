<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CateringMenu extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'catering_menus';
    protected $fillable = ['uuid', 'tenant_id', 'catering_company_id', 'correlation_id', 'name', 'description', 'price_kopecks', 'items_json', 'for_person_count', 'is_active', 'available_days', 'tags'];

    protected $casts = ['price_kopecks' => 'integer', 'items_json' => 'json', 'for_person_count' => 'integer', 'is_active' => 'boolean', 'available_days' => 'json', 'tags' => 'json'];

    public function company() { return $this->belongsTo(CateringCompany::class, 'catering_company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('catering_menus.tenant_id', tenant()->id));
    }
}

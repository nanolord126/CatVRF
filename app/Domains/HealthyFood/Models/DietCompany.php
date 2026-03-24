<?php declare(strict_types=1);

namespace App\Domains\HealthyFood\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class DietCompany extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'diet_companies';
    protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'phone', 'address', 'is_verified', 'commission_percent', 'min_order', 'tags'];
    protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

    public function meals() { return $this->hasMany(HealthyMeal::class, 'company_id'); }
    public function orders() { return $this->hasMany(HealthyMealOrder::class, 'company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('diet_companies.tenant_id', tenant()->id));
    }
}

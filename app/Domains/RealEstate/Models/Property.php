<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;

final class Property extends Model
{
    protected $table = "real_estate_properties";

    protected $fillable = [
        "tenant_id", "business_group_id", "uuid", "correlation_id", "title",
        "description", "address", "lat", "lon", "price", "type", "status",
        "photos", "documents", "features", "area_sqm", "is_active"
    ];

    protected $casts = [
        "photos" => "json", "documents" => "json", "features" => "json",
        "is_active" => "boolean", "price" => "decimal:2", "lat" => "decimal:8",
        "lon" => "decimal:8", "area_sqm" => "decimal:2"
    ];

    protected static function booted(): void
    {
        static::addGlobalScope("tenant", function (Builder $query): void {
            if (app()->bound("tenant") && app("tenant") instanceof Tenant) {
                $query->where("tenant_id", app("tenant")->id);
            }
        });

        static::creating(function (Model $model): void {
            if (!$model->uuid) { $model->uuid = (string) \Illuminate\Support\Str::uuid(); }
            if (!$model->correlation_id) {
                $model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function businessGroup(): BelongsTo { return $this->belongsTo(BusinessGroup::class); }
}

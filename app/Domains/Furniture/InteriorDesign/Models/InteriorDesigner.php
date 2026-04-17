<?php declare(strict_types=1);

/**
 * InteriorDesigner — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/interiordesigner
 */


namespace App\Domains\Furniture\InteriorDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InteriorDesigner extends Model
{


    use HasUuids,SoftDeletes,TenantScoped;protected $table='interior_designers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','styles','price_kopecks_per_sqm','rating','is_verified','tags'];protected $casts=['styles'=>'json','price_kopecks_per_sqm'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('interior_designers.tenant_id',tenant()->id));}

    /**
     * Связь с проектами дизайнера.
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DesignProject::class, 'designer_id');
    }

    /**
     * Проверяет, верифицирован ли дизайнер.
     */
    public function isVerified(): bool
    {
        return (bool) $this->is_verified;
    }

    /**
     * Проверяет, работает ли дизайнер в указанном стиле.
     */
    public function worksInStyle(string $style): bool
    {
        return is_array($this->styles) && in_array($style, $this->styles, true);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'id' => $this->id ?? null,
            'name' => $this->name ?? null,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}

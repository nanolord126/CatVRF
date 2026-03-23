<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Модель лекарственного средства — КАНОН 2026.
 */
final class Medicine extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = "pharmacy_medicines";

    protected $fillable = [
        "uuid",
        "tenant_id",
        "business_group_id",
        "correlation_id",
        "name",
        "sku",
        "barcode",
        "description",
        "active_ingredient",
        "dosage",
        "form_factor",
        "is_prescription_required",
        "is_refrigerated",
        "price_kopecks",
        "current_stock",
        "min_stock_threshold",
        "tags",
        "meta"
    ];

    protected $casts = [
        "is_prescription_required" => "boolean",
        "is_refrigerated" => "boolean",
        "price_kopecks" => "integer",
        "current_stock" => "integer",
        "tags" => "array",
        "meta" => "array",
    ];

    /**
     * Глобальный скопинг для тенанта уже в трейте.
     */
     
    public function pharmacy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
}

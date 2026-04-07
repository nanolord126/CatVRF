<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Medicine extends Model
{
    use HasFactory;

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function pharmacy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
        {
            return $this->belongsTo(Pharmacy::class);
        }
}

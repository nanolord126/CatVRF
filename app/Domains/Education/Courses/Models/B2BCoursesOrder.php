<?php declare(strict_types=1);

/**
 * B2BCoursesOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bcoursesorder
 */


namespace App\Domains\Education\Courses\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BCoursesOrder extends Model
{

    use HasFactory;

    use SoftDeletes;

        protected $table = 'b2b_courses_orders';

        protected $fillable = [
            'uuid', 'tenant_id', 'b2b_courses_storefront_id', 'user_id', 'order_number',
            'company_contact_person', 'company_phone', 'items', 'total_amount',
            'commission_amount', 'status', 'rejection_reason', 'correlation_id', 'tags'
        ];

        protected $casts = [
            'items' => 'json',
            'total_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant() && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function storefront(): BelongsTo
        {
            return $this->belongsTo(B2BCoursesStorefront::class, 'b2b_courses_storefront_id');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}

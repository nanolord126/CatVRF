<?php

/**
 * Fixer script: corrects all Flowers Models for CatVRF Canon 2026 compliance.
 * Run once, then delete this file.
 */

$dir = __DIR__ . '/app/Domains/Flowers/Models';
$files = [];

// ─── 1. B2BFlowerOrder ───────────────────────────────────────────────
$files['B2BFlowerOrder.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * B2B-заказ цветов.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $storefront_id
 * @property int $shop_id
 * @property string $order_number
 * @property string $status
 * @property string $payment_status
 */
final class B2BFlowerOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'b2b_flower_orders';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'storefront_id',
        'shop_id',
        'order_number',
        'subtotal',
        'bulk_discount',
        'commission_amount',
        'total_amount',
        'delivery_address',
        'delivery_location',
        'delivery_date',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'delivery_location' => 'json',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'bulk_discount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BFlowerStorefront::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }
}
PHP;

// ─── 2. B2BFlowerStorefront ──────────────────────────────────────────
$files['B2BFlowerStorefront.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * B2B-витрина цветочного магазина.
 *
 * @property int $id
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $shop_id
 * @property string $company_inn
 * @property string $company_name
 * @property bool $is_verified
 * @property bool $is_active
 */
final class B2BFlowerStorefront extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'b2b_flower_storefronts';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'shop_id',
        'company_inn',
        'company_name',
        'company_address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'bulk_discounts',
        'min_order_items',
        'delivery_schedule',
        'is_verified',
        'is_active',
    ];

    protected $casts = [
        'bulk_discounts' => 'json',
        'delivery_schedule' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(B2BFlowerOrder::class, 'storefront_id');
    }
}
PHP;

// ─── 3. Bouquet ──────────────────────────────────────────────────────
$files['Bouquet.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Букет — составная композиция из цветов и расходников.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $shop_id
 * @property string $name
 * @property string|null $description
 * @property array|null $images
 * @property array|null $flowers_composition
 * @property string $uuid
 * @property string|null $correlation_id
 * @property bool $is_available
 */
final class Bouquet extends Model
{
    use HasFactory;

    protected $table = 'flower_bouquets';

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'name',
        'description',
        'images',
        'flowers_composition',
        'price',
        'consumables_json',
        'is_available',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'images' => 'json',
        'flowers_composition' => 'json',
        'consumables_json' => 'json',
        'tags' => 'json',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FlowerOrder::class, 'bouquet_id');
    }
}
PHP;

// ─── 4. FlowerCategory ──────────────────────────────────────────────
$files['FlowerCategory.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Категория цветочных товаров.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $correlation_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class FlowerCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_categories';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'slug',
        'description',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    /**
     * Букеты данной категории.
     */
    public function bouquets(): HasMany
    {
        return $this->hasMany(Bouquet::class, 'category_id');
    }
}
PHP;

// ─── 5. FlowerConsumable ─────────────────────────────────────────────
$files['FlowerConsumable.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Расходный материал для флористики (лента, упаковка, оазис и т.д.).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $shop_id
 * @property string $name
 * @property string $type
 * @property int $current_stock
 * @property int $min_stock_threshold
 * @property string $unit
 * @property string $uuid
 * @property string|null $correlation_id
 */
final class FlowerConsumable extends Model
{
    use HasFactory;

    protected $table = 'flower_consumables';

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'name',
        'type',
        'current_stock',
        'min_stock_threshold',
        'unit',
        'price_per_unit',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'json',
        'price_per_unit' => 'decimal:2',
        'current_stock' => 'integer',
        'min_stock_threshold' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }
}
PHP;

// ─── 6. FlowerDelivery ──────────────────────────────────────────────
$files['FlowerDelivery.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Доставка цветочного заказа.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $order_id
 * @property int $shop_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $picked_up_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 */
final class FlowerDelivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_deliveries';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'order_id',
        'shop_id',
        'courier_name',
        'courier_phone',
        'current_location',
        'status',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
        'delivery_notes',
        'route',
    ];

    protected $casts = [
        'current_location' => 'json',
        'route' => 'json',
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FlowerOrder::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }
}
PHP;

// ─── 7. FlowerOrder ─────────────────────────────────────────────────
$files['FlowerOrder.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Заказ цветов (B2C).
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $shop_id
 * @property int $user_id
 * @property string $order_number
 * @property string $status
 * @property string $payment_status
 */
final class FlowerOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_orders';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'shop_id',
        'user_id',
        'order_number',
        'subtotal',
        'delivery_fee',
        'commission_amount',
        'total_amount',
        'recipient_name',
        'recipient_phone',
        'delivery_address',
        'delivery_location',
        'delivery_date',
        'delivery_time_slot',
        'message',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'delivery_location' => 'json',
        'delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlowerOrderItem::class, 'order_id');
    }

    public function delivery(): HasMany
    {
        return $this->hasMany(FlowerDelivery::class, 'order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FlowerReview::class, 'order_id');
    }
}
PHP;

// ─── 8. FlowerOrderItem ─────────────────────────────────────────────
$files['FlowerOrderItem.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Позиция заказа цветов.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property array|null $customizations
 */
final class FlowerOrderItem extends Model
{
    use HasFactory;

    protected $table = 'flower_order_items';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'correlation_id',
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'customizations',
    ];

    protected $casts = [
        'customizations' => 'json',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FlowerOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FlowerProduct::class);
    }
}
PHP;

// ─── 9. FlowerPortfolioItem ─────────────────────────────────────────
$files['FlowerPortfolioItem.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Работа из портфолио цветочного магазина.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $shop_id
 * @property string $image_path
 * @property string|null $title
 * @property array|null $tags
 * @property string|null $correlation_id
 */
final class FlowerPortfolioItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_portfolio';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'shop_id',
        'image_path',
        'title',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'tags' => 'json',
    ];

    protected $hidden = [
        'id',
        'tenant_id',
    ];

    /**
     * Tenant-scoping и автогенерация UUID / correlation_id.
     */
    protected static function booted(): void
    {
        static::creating(function (FlowerPortfolioItem $item): void {
            $item->uuid = $item->uuid ?: (string) Str::uuid();
            $item->tenant_id = $item->tenant_id
                ?? (function_exists('tenant') && tenant() ? tenant('id') : 0);
            $item->correlation_id = $item->correlation_id
                ?: (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    /**
     * Магазин, которому принадлежит работа.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }
}
PHP;

// ─── 10. FlowerProduct ──────────────────────────────────────────────
$files['FlowerProduct.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Цветочный товар (розы, тюльпаны, аксессуары и т.д.).
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $shop_id
 * @property string $name
 * @property bool $is_available
 * @property bool $seasonal
 */
final class FlowerProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_products';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'shop_id',
        'name',
        'description',
        'images',
        'product_type',
        'flowers',
        'add_ons',
        'price',
        'stock',
        'min_order_days',
        'rating',
        'review_count',
        'orders_count',
        'is_available',
        'seasonal',
        'tags',
    ];

    protected $casts = [
        'images' => 'json',
        'flowers' => 'json',
        'add_ons' => 'json',
        'tags' => 'json',
        'is_available' => 'boolean',
        'seasonal' => 'boolean',
        'price' => 'decimal:2',
        'rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(FlowerOrderItem::class, 'product_id');
    }
}
PHP;

// ─── 11. FlowerReview ────────────────────────────────────────────────
$files['FlowerReview.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Отзыв на цветочный заказ.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $order_id
 * @property int $shop_id
 * @property int $user_id
 * @property bool $verified_purchase
 */
final class FlowerReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_reviews';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'order_id',
        'shop_id',
        'user_id',
        'quality_rating',
        'delivery_rating',
        'freshness_rating',
        'overall_rating',
        'comment',
        'photos',
        'status',
        'helpful_count',
        'unhelpful_count',
        'verified_purchase',
    ];

    protected $casts = [
        'photos' => 'json',
        'verified_purchase' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FlowerOrder::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
PHP;

// ─── 12. FlowerShop ─────────────────────────────────────────────────
$files['FlowerShop.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Цветочный магазин.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $user_id
 * @property int|null $business_group_id
 * @property string $shop_name
 * @property bool $is_verified
 * @property bool $is_active
 * @property float $rating
 */
final class FlowerShop extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_shops';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'user_id',
        'business_group_id',
        'shop_name',
        'description',
        'phone',
        'address',
        'location',
        'schedule',
        'delivery_radius_km',
        'delivery_fee',
        'rating',
        'review_count',
        'orders_count',
        'is_verified',
        'is_active',
        'tags',
    ];

    protected $casts = [
        'location' => 'json',
        'schedule' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(FlowerProduct::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FlowerOrder::class, 'shop_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(FlowerDelivery::class, 'shop_id');
    }

    public function b2bStorefronts(): HasMany
    {
        return $this->hasMany(B2BFlowerStorefront::class, 'shop_id');
    }

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BFlowerOrder::class, 'shop_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FlowerReview::class, 'shop_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(FlowerSubscription::class, 'shop_id');
    }
}
PHP;

// ─── 13. FlowerSubscription ─────────────────────────────────────────
$files['FlowerSubscription.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Подписка на регулярную доставку цветов.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property int $tenant_id
 * @property int $shop_id
 * @property int $user_id
 * @property string $subscription_name
 * @property string $frequency
 * @property string $status
 * @property string $payment_status
 */
final class FlowerSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_subscriptions';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'shop_id',
        'user_id',
        'subscription_name',
        'description',
        'products',
        'frequency',
        'price_per_delivery',
        'commission_amount',
        'start_date',
        'end_date',
        'deliveries_completed',
        'deliveries_remaining',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'products' => 'json',
        'start_date' => 'date',
        'end_date' => 'date',
        'price_per_delivery' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }
}
PHP;

// ─── 14. Perfume ─────────────────────────────────────────────────────
$files['Perfume.php'] = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Парфюмерия в цветочном магазине.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $shop_id
 * @property string $brand
 * @property string $name
 * @property string|null $description
 * @property array|null $fragrance_notes
 * @property int $volume_ml
 * @property bool $is_available
 * @property string $uuid
 * @property string|null $correlation_id
 */
final class Perfume extends Model
{
    use HasFactory;

    protected $table = 'flower_perfumes';

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'brand',
        'name',
        'description',
        'fragrance_notes',
        'volume_ml',
        'price',
        'stock',
        'is_available',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'fragrance_notes' => 'json',
        'tags' => 'json',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
        'volume_ml' => 'integer',
        'stock' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FlowerOrder::class);
    }
}
PHP;

// ─── Apply fixes ─────────────────────────────────────────────────────

$fixed = 0;
$errors = [];

foreach ($files as $name => $content) {
    $path = $dir . '/' . $name;

    if (!file_exists($path)) {
        $errors[] = "NOT FOUND: $name";
        continue;
    }

    // Normalize to CRLF
    $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\n", "\r\n", $content);

    file_put_contents($path, $content);
    $fixed++;

    // Quick line count
    $lines = substr_count($content, "\r\n") + 1;
    echo "  Fixed: $name ($lines lines)\n";
}

echo "\n=== Total fixed: $fixed / " . count($files) . " ===\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $e) {
        echo "  $e\n";
    }
}

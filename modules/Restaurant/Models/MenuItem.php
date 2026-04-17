<?php declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'restaurant_menu_items';

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'uuid',
        'name',
        'description',
        'category',
        'price',
        'is_available',
        'is_featured',
        'preparation_time',
        'calories',
        'allergens',
        'image_url',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'preparation_time' => 'integer',
        'calories' => 'integer',
        'allergens' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Категории позиций меню.
     */
    public const CATEGORY_APPETIZER = 'appetizer';
    public const CATEGORY_SOUP = 'soup';
    public const CATEGORY_SALAD = 'salad';
    public const CATEGORY_MAIN_COURSE = 'main_course';
    public const CATEGORY_DESSERT = 'dessert';
    public const CATEGORY_DRINK = 'drink';
    public const CATEGORY_ALCOHOL = 'alcohol';
    public const CATEGORY_SIDE_DISH = 'side_dish';

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить ресторан.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }
}

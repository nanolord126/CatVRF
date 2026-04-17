<?php declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'restaurants';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'description',
        'address',
        'city',
        'lat',
        'lon',
        'category',
        'cuisine_type',
        'price_range',
        'rating',
        'review_count',
        'is_delivery_available',
        'is_pickup_available',
        'is_dine_in_available',
        'average_preparation_time_minutes',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'rating' => 'float',
        'review_count' => 'integer',
        'is_delivery_available' => 'boolean',
        'is_pickup_available' => 'boolean',
        'is_dine_in_available' => 'boolean',
        'average_preparation_time_minutes' => 'integer',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Категории заведений.
     */
    public const CATEGORY_FINE_DINING = 'fine_dining';
    public const CATEGORY_CASUAL = 'casual';
    public const CATEGORY_FAST_FOOD = 'fast_food';
    public const CATEGORY_CAFE = 'cafe';
    public const CATEGORY_BISTRO = 'bistro';
    public const CATEGORY_BAR = 'bar';
    public const CATEGORY_PUB = 'pub';
    public const CATEGORY_STEAKHOUSE = 'steakhouse';
    public const CATEGORY_SUSHI = 'sushi';
    public const CATEGORY_PIZZERIA = 'pizzeria';
    public const CATEGORY_SEAFOOD = 'seafood';
    public const CATEGORY_VEGETARIAN = 'vegetarian';
    public const CATEGORY_VEGAN = 'vegan';
    public const CATEGORY_BAKERY = 'bakery';
    public const CATEGORY_COFFEE_SHOP = 'coffee_shop';
    public const CATEGORY_FOOD_TRUCK = 'food_truck';
    public const CATEGORY_BUFFET = 'buffet';
    public const CATEGORY_GASTROPUB = 'gastropub';
    public const CATEGORY_BRASSERIE = 'brasserie';
    public const CATEGORY_TEA_HOUSE = 'tea_house';

    /**
     * Типы кухонь.
     */
    public const CUISINE_ITALIAN = 'italian';
    public const CUISINE_JAPANESE = 'japanese';
    public const CUISINE_CHINESE = 'chinese';
    public const CUISINE_FRENCH = 'french';
    public const CUISINE_RUSSIAN = 'russian';
    public const CUISINE_MEXICAN = 'mexican';
    public const CUISINE_THAI = 'thai';
    public const CUISINE_INDIAN = 'indian';
    public const CUISINE_AMERICAN = 'american';
    public const CUISINE_GEORGIAN = 'georgian';
    public const CUISINE_ARMENIAN = 'armenian';
    public const CUISINE_MEDITERRANEAN = 'mediterranean';
    public const CUISINE_EUROPEAN = 'european';
    public const CUISINE_ASIAN = 'asian';
    public const CUISINE_FUSION = 'fusion';

    /**
     * Комиссия платформы по умолчанию (в процентах).
     */
    public const DEFAULT_COMMISSION_PERCENT = 12;

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

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Получить меню ресторана.
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'restaurant_id');
    }

    /**
     * Получить бронирования.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'restaurant_id');
    }

    /**
     * Получить отзывы.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'restaurant_id');
    }

    /**
     * Получить среднюю оценку.
     */
    public function getAverageRating(): float
    {
        return (float) $this->rating;
    }

    /**
     * Увеличить количество отзывов.
     */
    public function incrementReviewCount(): void
    {
        $this->increment('review_count');
    }

    /**
     * Получить количество активных позиций меню.
     */
    public function getActiveMenuItemsCount(): int
    {
        return $this->menuItems()->where('is_available', true)->count();
    }

    /**
     * Проверить, активно ли заведение.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Проверить, проверено ли заведение.
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Получить название категории.
     */
    public function getCategoryName(): string
    {
        return match($this->category) {
            self::CATEGORY_FINE_DINING => 'Высокая кухня',
            self::CATEGORY_CASUAL => 'Обычное кафе',
            self::CATEGORY_FAST_FOOD => 'Фастфуд',
            self::CATEGORY_CAFE => 'Кафе',
            self::CATEGORY_BISTRO => 'Бистро',
            self::CATEGORY_BAR => 'Бар',
            self::CATEGORY_PUB => 'Паб',
            self::CATEGORY_STEAKHOUSE => 'Стейкхаус',
            self::CATEGORY_SUSHI => 'Суши-бар',
            self::CATEGORY_PIZZERIA => 'Пиццерия',
            self::CATEGORY_SEAFOOD => 'Морепродукты',
            self::CATEGORY_VEGETARIAN => 'Вегетарианское',
            self::CATEGORY_VEGAN => 'Веганское',
            self::CATEGORY_BAKERY => 'Пекарня',
            self::CATEGORY_COFFEE_SHOP => 'Кофейня',
            self::CATEGORY_FOOD_TRUCK => 'Фуд-трак',
            self::CATEGORY_BUFFET => 'Шведский стол',
            self::CATEGORY_GASTROPUB => 'Гастропаб',
            self::CATEGORY_BRASSERIE => 'Брассери',
            self::CATEGORY_TEA_HOUSE => 'Чайный дом',
            default => 'Другое',
        };
    }

    /**
     * Получить название типа кухни.
     */
    public function getCuisineTypeName(): string
    {
        return match($this->cuisine_type) {
            self::CUISINE_ITALIAN => 'Итальянская',
            self::CUISINE_JAPANESE => 'Японская',
            self::CUISINE_CHINESE => 'Китайская',
            self::CUISINE_FRENCH => 'Французская',
            self::CUISINE_RUSSIAN => 'Русская',
            self::CUISINE_MEXICAN => 'Мексиканская',
            self::CUISINE_THAI => 'Тайская',
            self::CUISINE_INDIAN => 'Индийская',
            self::CUISINE_AMERICAN => 'Американская',
            self::CUISINE_GEORGIAN => 'Грузинская',
            self::CUISINE_ARMENIAN => 'Армянская',
            self::CUISINE_MEDITERRANEAN => 'Средиземноморская',
            self::CUISINE_EUROPEAN => 'Европейская',
            self::CUISINE_ASIAN => 'Азиатская',
            self::CUISINE_FUSION => 'Фьюжн',
            default => 'Другая',
        };
    }
}

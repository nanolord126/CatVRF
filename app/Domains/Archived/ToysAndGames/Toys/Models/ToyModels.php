<?php declare(strict_types=1);

namespace App\Domains\Archived\ToysAndGames\Toys\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ToysDomainTrait extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static function booted(): void


        {


            // 1. Automatic UUID and Correlation Logic


            static::creating(function ($model) {


                if (empty($model->uuid)) {


                    $model->uuid = (string) Str::uuid();


                }


                if (property_exists($model, 'correlation_id') && empty($model->correlation_id)) {


                    $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());


                }


            });


            // 2. Global Multi-tenant Scoping (Lute Mode - No leaks)


            static::addGlobalScope('tenant_isolation', function (Builder $builder) {


                if (auth()->check() && method_exists(auth()->user(), 'tenant')) {


                    $builder->where('tenant_id', auth()->user()->tenant_id);


                }


            });


        }


    }


    /**


     * ToyStore Model (L1)


     * Represents a logical or physical inventory hub.


     */


    final class ToyStore extends Model


    {


        use ToysDomainTrait;


        protected $table = 'toy_stores';


        protected $fillable = ['uuid', 'tenant_id', 'name', 'location', 'metadata', 'correlation_id'];


        protected $casts = ['metadata' => 'json'];


        public function toys(): HasMany


        {


            return $this->hasMany(Toy::class, 'store_id');


        }


    }


    /**


     * ToyCategory Model (L1)


     * Taxonomy: Puzzles, Lego, Sensory, Boards.


     */


    final class ToyCategory extends Model


    {


        use ToysDomainTrait;


        protected $table = 'toy_categories';


        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'correlation_id'];


        public function toys(): HasMany


        {


            return $this->hasMany(Toy::class, 'category_id');


        }


    }


    /**


     * AgeGroup Model (L1)


     * Critical for AI recommendation matching (min/max months).


     */


    final class AgeGroup extends Model


    {


        use ToysDomainTrait;


        protected $table = 'age_groups';


        protected $fillable = ['uuid', 'tenant_id', 'name', 'min_age_months', 'max_age_months', 'correlation_id'];


        public function toys(): HasMany


        {


            return $this->hasMany(Toy::class, 'age_group_id');


        }


    }


    /**


     * Toy Model (L1)


     * Core business entity for physical goods.


     */


    final class Toy extends Model


    {


        use ToysDomainTrait;


        protected $table = 'toys';


        protected $fillable = [


            'uuid', 'tenant_id', 'store_id', 'category_id', 'age_group_id',


            'title', 'sku', 'description', 'price_b2c', 'price_b2b',


            'stock_quantity', 'safety_certification', 'material_type',


            'is_gift_wrappable', 'is_active', 'metadata', 'tags', 'correlation_id'


        ];


        protected $casts = [


            'metadata' => 'json',


            'tags' => 'json',


            'is_active' => 'boolean',


            'is_gift_wrappable' => 'boolean'


        ];


        public function store(): BelongsTo { return $this->belongsTo(ToyStore::class, 'store_id'); }


        public function category(): BelongsTo { return $this->belongsTo(ToyCategory::class, 'category_id'); }


        public function ageGroup(): BelongsTo { return $this->belongsTo(AgeGroup::class, 'age_group_id'); }


        public function reviews(): HasMany { return $this->hasMany(ToyReview::class, 'toy_id'); }


    }


    /**


     * ToyOrder Model (L1)


     * Master transaction record supporting B2B (Company) and B2C (User).


     */


    final class ToyOrder extends Model


    {


        use ToysDomainTrait;


        protected $table = 'toy_orders';


        protected $fillable = [


            'uuid', 'tenant_id', 'user_id', 'b2b_company_id', 'store_id',


            'total_amount', 'status', 'payment_status', 'gift_requested',


            'correlation_id', 'metadata'


        ];


        protected $casts = [


            'metadata' => 'json',


            'gift_requested' => 'boolean'


        ];


        public function store(): BelongsTo { return $this->belongsTo(ToyStore::class, 'store_id'); }


        public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }


        public function b2bCompany(): BelongsTo { return $this->belongsTo(\App\Models\BusinessGroup::class, 'b2b_company_id'); }


    }


    /**


     * ToySubscriptionBox Model (L1)


     * Monthly recurring revenue / rotation model.


     */


    final class ToySubscriptionBox extends Model


    {


        use ToysDomainTrait;


        protected $table = 'toy_subscription_boxes';


        protected $fillable = [


            'uuid', 'tenant_id', 'user_id', 'age_group_id',


            'monthly_limit', 'status', 'is_paid',


            'next_delivery_at', 'last_sent_at', 'metadata', 'correlation_id'


        ];


        protected $casts = [


            'metadata' => 'json',


            'is_paid' => 'boolean',


            'next_delivery_at' => 'datetime',


            'last_sent_at' => 'datetime'


        ];


        public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }


        public function ageGroup(): BelongsTo { return $this->belongsTo(AgeGroup::class, 'age_group_id'); }


    }


    /**


     * ToyReview Model (L1)


     * Feedback and sentiment loop.


     */


    final class ToyReview extends Model


    {


        use ToysDomainTrait;


        protected $table = 'toy_reviews';


        protected $fillable = [


            'uuid', 'tenant_id', 'toy_id', 'user_id',


            'rating', 'comment', 'metadata', 'correlation_id'


        ];


        protected $casts = ['metadata' => 'json'];


        public function toy(): BelongsTo { return $this->belongsTo(Toy::class, 'toy_id'); }


        public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
}

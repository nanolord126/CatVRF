<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BooksDomainTrait extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->correlation_id)) {
                    $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
                }
            });

            // Global Scope for Isolation by Tenant (Canon 2026)
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (auth()->check() && !auth()->user()->is_admin) {
                    $builder->where('tenant_id', filament()->getTenant()?->id ?? auth()->user()->tenant_id);
                }
            });
        }
    }

    /**
     * BookAuthor Model (L1/9)
     */
    final class BookAuthor extends Model
    {
        use BooksDomainTrait, SoftDeletes;
        protected $table = 'book_authors';
        protected $fillable = ['tenant_id', 'uuid', 'name', 'biography', 'nationality', 'birth_date', 'tags', 'correlation_id'];
        protected $casts = ['tags' => 'json', 'birth_date' => 'date'];
    }

    /**
     * Genre Model (L1/9)
     */
    final class BookGenre extends Model
    {
        use BooksDomainTrait;
        protected $table = 'book_genres';
        protected $fillable = ['tenant_id', 'uuid', 'name', 'description', 'popularity_index', 'correlation_id'];
    }

    /**
     * BookStore Model (L1/9)
     */
    final class BookStore extends Model
    {
        use BooksDomainTrait;
        protected $table = 'book_stores';
        protected $fillable = ['tenant_id', 'uuid', 'name', 'address', 'contact_phone', 'has_lounge', 'correlation_id'];
        protected $casts = ['has_lounge' => 'boolean'];
    }

    /**
     * Primary Book Model (L1/9)
     */
    final class Book extends Model
    {
        use BooksDomainTrait, SoftDeletes;
        protected $table = 'books';
        protected $fillable = [
            'tenant_id', 'uuid', 'store_id', 'author_id', 'genre_id', 'title', 'isbn',
            'description', 'format', 'price_b2c', 'price_b2b', 'stock_quantity',
            'page_count', 'language', 'metadata', 'tags', 'is_active', 'correlation_id'
        ];
        protected $casts = ['metadata' => 'json', 'tags' => 'json', 'is_active' => 'boolean'];

        public function author() { return $this->belongsTo(BookAuthor::class, 'author_id'); }
        public function genre() { return $this->belongsTo(BookGenre::class, 'genre_id'); }
        public function store() { return $this->belongsTo(BookStore::class, 'store_id'); }
    }

    /**
     * SubscriptionBox Model (L1/9)
     */
    final class BookSubscriptionBox extends Model
    {
        use BooksDomainTrait;
        protected $table = 'book_subscription_boxes';
        protected $fillable = ['tenant_id', 'uuid', 'name', 'description', 'price_monthly', 'genre_focus', 'items_per_box', 'is_giftable', 'correlation_id'];
        protected $casts = ['genre_focus' => 'json', 'is_giftable' => 'boolean'];
    }

    /**
     * Order Model (L1/9)
     */
    final class BookOrder extends Model
    {
        use BooksDomainTrait;
        protected $table = 'book_orders';
        protected $fillable = ['tenant_id', 'uuid', 'user_id', 'type', 'order_number', 'total_amount', 'status', 'shipping_address', 'order_items', 'is_gift', 'gift_message', 'correlation_id'];
        protected $casts = ['order_items' => 'json', 'is_gift' => 'boolean'];
    }

    /**
     * Review Model (L1/9)
     */
    final class BookReview extends Model
    {
        use BooksDomainTrait;
        protected $table = 'book_reviews';
        protected $fillable = ['tenant_id', 'uuid', 'book_id', 'user_id', 'rating', 'comment', 'mood_tags', 'is_verified_purchase', 'correlation_id'];
        protected $casts = ['mood_tags' => 'json', 'is_verified_purchase' => 'boolean'];
}

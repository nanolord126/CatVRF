<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

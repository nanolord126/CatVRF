<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
     * Order Model (L1/9)
     */
final class BookOrder extends Model
{
        use BooksDomainTrait, TenantScoped;
        protected $table = 'book_orders';
        protected $fillable = ['tenant_id', 'uuid', 'user_id', 'type', 'order_number', 'total_amount', 'status', 'shipping_address', 'order_items', 'is_gift', 'gift_message', 'correlation_id'];
        protected $casts = ['order_items' => 'json', 'is_gift' => 'boolean'];
    }

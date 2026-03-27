<?php declare(strict_types=1);

n

/**
 * BookOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BookOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\BooksAndLiterature\BooksAndLiterature\Books\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
amespace App\Domains\BooksAndLiterature\BooksAndLiterature\Books\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class BookOrder extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'book_orders';
    protected $fillable = ['uuid', 'tenant_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'items_json', 'tags'];
    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'items_json' => 'json', 'tags' => 'json'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('book_orders.tenant_id', tenant()->id));
    }
}

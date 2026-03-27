<?php declare(strict_types=1);

n

/**
 * Book
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Book();
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

final class Book extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'books';
    protected $fillable = ['uuid', 'tenant_id', 'publisher_id', 'correlation_id', 'title', 'author', 'price_kopecks', 'genre', 'format', 'is_available', 'tags'];
    protected $casts = ['price_kopecks' => 'integer', 'is_available' => 'boolean', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function orders() { return $this->hasMany(BookOrder::class, 'book_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('books.tenant_id', tenant()->id));
    }
}

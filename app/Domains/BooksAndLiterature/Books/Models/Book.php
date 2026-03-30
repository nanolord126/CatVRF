<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Book extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

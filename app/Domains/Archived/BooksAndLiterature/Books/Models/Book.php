<?php declare(strict_types=1);

namespace App\Domains\Archived\BooksAndLiterature\Books\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Book extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'books';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'author_id',
            'correlation_id',
            'title',
            'description',
            'isbn',
            'published_at',
            'price',
            'tags',
            'meta',
        ];

        protected $casts = [
            'published_at' => 'datetime',
            'tags' => 'json',
            'meta' => 'json',
            'price' => 'integer',
        ];

        public function orders() {
            return $this->hasMany(BookOrder::class, 'book_id');
        }
    }


        protected static function booted(): void


        {


            static::addGlobalScope('tenant', fn($q) => $q->where('books.tenant_id', tenant()->id));


        }
}

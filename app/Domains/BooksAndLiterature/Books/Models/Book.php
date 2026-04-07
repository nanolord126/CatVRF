<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
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

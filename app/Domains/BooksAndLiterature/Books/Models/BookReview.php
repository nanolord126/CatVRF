<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

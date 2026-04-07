<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
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

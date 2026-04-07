<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;

use BooksDomainTrait, SoftDeletes;
use BooksDomainTrait;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
     * Genre Model (L1/9)
     */
final class BookGenre extends Model
{
        use BooksDomainTrait, TenantScoped;
        protected $table = 'book_genres';
        protected $fillable = ['tenant_id', 'uuid', 'name', 'description', 'popularity_index', 'correlation_id'];
    }

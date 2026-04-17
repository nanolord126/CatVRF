<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;


/**
     * HobbyCategory Model
     */
final class HobbyCategory extends Model
{
        use HobbyDomainTrait, TenantScoped;

        protected $table = 'hobby_categories';

        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'icon', 'meta'];

        protected $casts = ['meta' => 'json'];

        public function products(): HasMany
        {
            return $this->hasMany(HobbyProduct::class, 'category_id');
        }
    }

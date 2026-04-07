<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\SoftDeletes;
/**
     * HobbyStore Model
     * Represents a business entity (store) within the Hobby & Craft domain.
     */
final class HobbyStore extends Model
{
        use SoftDeletes, HobbyDomainTrait;

        protected $table = 'hobby_stores';

        protected $fillable = [
            'uuid', 'tenant_id', 'name', 'slug', 'description',
            'contact_email', 'website_url', 'settings', 'is_active', 'correlation_id'
        ];

        protected $casts = [
            'settings' => 'json',
            'is_active' => 'boolean',
        ];

        public function products(): HasMany
        {
            return $this->hasMany(HobbyProduct::class, 'store_id');
        }

        public function tutorials(): HasMany
        {
            return $this->hasMany(HobbyTutorial::class, 'store_id');
        }
    }

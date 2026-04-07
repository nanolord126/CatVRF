<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Models;

use HasFactory;
use HobbyDomainTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SoftDeletes, HobbyDomainTrait;

/**
     * HobbyTutorial Model
     */
final class HobbyTutorial extends Model
{
        use HobbyDomainTrait;

        protected $table = 'hobby_tutorials';

        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'title', 'content_html', 'video_url',
            'price', 'skill_level', 'required_product_ids', 'is_published', 'correlation_id'
        ];

        protected $casts = [
            'required_product_ids' => 'json',
            'is_published' => 'boolean',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(HobbyStore::class, 'store_id');
        }
    }

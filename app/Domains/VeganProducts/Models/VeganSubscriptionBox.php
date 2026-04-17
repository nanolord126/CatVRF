<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Models;



use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
     * VeganSubscriptionBox Model - Weekly/Monthly curated plant-based items.
     */
final class VeganSubscriptionBox extends Model
{
        protected $table = 'vegan_subscription_boxes';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'description', 'price_monthly', 'plan_type', 'included_product_ids', 'is_active', 'correlation_id'];
        protected $casts = ['included_product_ids' => 'json', 'price_monthly' => 'integer', 'is_active' => 'boolean'];

        public function reviews(): MorphMany { return $this->morphMany(VeganReview::class, 'reviewable'); }
    }

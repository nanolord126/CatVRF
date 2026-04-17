<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


/**
     * ToyReview Model (L1)
     * Feedback and sentiment loop.
     */
final class ToyReview extends Model
{
        use ToysDomainTrait, TenantScoped;
        protected $table = 'toy_reviews';
        protected $fillable = [
            'uuid', 'tenant_id', 'toy_id', 'user_id',
            'rating', 'comment', 'metadata', 'correlation_id'
        ];
        protected $casts = ['metadata' => 'json'];

        public function toy(): BelongsTo { return $this->belongsTo(Toy::class, 'toy_id'); }
        public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
}

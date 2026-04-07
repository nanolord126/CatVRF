<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


/**
     * ToyOrder Model (L1)
     * Master transaction record supporting B2B (Company) and B2C (User).
     */
final class ToyOrder extends Model
{
        use ToysDomainTrait;
        protected $table = 'toy_orders';
        protected $fillable = [
            'uuid', 'tenant_id', 'user_id', 'b2b_company_id', 'store_id',
            'total_amount', 'status', 'payment_status', 'gift_requested',
            'correlation_id', 'metadata'
        ];
        protected $casts = [
            'metadata' => 'json',
            'gift_requested' => 'boolean'
        ];

        public function store(): BelongsTo { return $this->belongsTo(ToyStore::class, 'store_id'); }
        public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
        public function b2bCompany(): BelongsTo { return $this->belongsTo(\App\Models\BusinessGroup::class, 'b2b_company_id'); }
    }

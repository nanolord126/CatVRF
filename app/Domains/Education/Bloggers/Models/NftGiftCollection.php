<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NftGiftCollection extends Model
{
    use HasFactory;

        protected $table = 'nft_gift_collections';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'collection_name',
            'collection_description',
            'collection_image_url',
            'ton_collection_address',
            'status',
            'started_at',
            'ended_at',
            'max_supply',
            'minted_count',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'tags' => 'json',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        protected $hidden = ['ton_collection_address', 'correlation_id'];

        public function gifts(): HasMany
        {
            return $this->hasMany(NftGift::class, 'nft_contract_address', 'ton_collection_address');
        }

        public function isActive(): bool
        {
            return $this->status === 'active' &&
                (! $this->started_at || $this->started_at <= Carbon::now()) &&
                (! $this->ended_at || $this->ended_at >= Carbon::now());
        }

        public function isFull(): bool
        {
            return $this->max_supply && $this->minted_count >= $this->max_supply;
        }
}

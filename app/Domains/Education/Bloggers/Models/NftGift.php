<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;

use Illuminate\Config\Repository as ConfigRepository;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NftGift extends Model
{

    use HasFactory, SoftDeletes;

        protected $table = 'nft_gifts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'stream_id',
            'sender_user_id',
            'recipient_user_id',
            'business_group_id',
            'gift_name',
            'gift_image_url',
            'gift_description',
            'gift_price',
            'gift_type',
            'ton_address',
            'nft_contract_address',
            'nft_address',
            'nft_token_id',
            'metadata_uri',
            'metadata',
            'minting_status',
            'minting_error',
            'ton_tx_hash',
            'minted_at',
            'upgrade_eligible_at',
            'is_upgraded',
            'upgraded_at',
            'tags',
            'correlation_id',
            'paid_at',
            'payment_id',
        ];

        protected $casts = [
            'gift_price' => 'decimal:2',
            'metadata' => 'json',
            'tags' => 'json',
            'minted_at' => 'datetime',
            'upgrade_eligible_at' => 'datetime',
            'upgraded_at' => 'datetime',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_upgraded' => 'boolean',
        ];

        protected $hidden = ['ton_address', 'correlation_id', 'nft_contract_address'];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('nft_gifts.tenant_id', tenant()->id);
            });
        }

        public function stream(): BelongsTo
        {
            return $this->belongsTo(Stream::class, 'stream_id');
        }

        public function sender(): BelongsTo
        {
            return $this->belongsTo(User::class, 'sender_user_id');
        }

        public function recipient(): BelongsTo
        {
            return $this->belongsTo(User::class, 'recipient_user_id');
        }

        public function isMinted(): bool
        {
            return $this->minting_status === 'minted' && ! empty($this->nft_address);
        }

        public function isPending(): bool
        {
            return $this->minting_status === 'pending';
        }

        public function isMinting(): bool
        {
            return $this->minting_status === 'minting';
        }

        public function hasFailed(): bool
        {
            return $this->minting_status === 'failed';
        }

        public function isExpired(): bool
        {
            return $this->minting_status === 'expired';
        }

        public function isEligibleForUpgrade(): bool
        {
            if ($this->is_upgraded || ! $this->isMinted()) {
                return false;
            }

            return $this->minted_at && Carbon::now()->diffInDays($this->minted_at) >= 14;
        }

        public function getTonExplorerUrl(): string
        {
            if (! $this->ton_tx_hash) {
                return '';
            }

            $network = $this->config->get('bloggers.ton.network') === 'mainnet' ? 'tonviewer.com' : 'testnet.tonviewer.com';

            return "https://{$network}/transaction/{$this->ton_tx_hash}";
        }
}

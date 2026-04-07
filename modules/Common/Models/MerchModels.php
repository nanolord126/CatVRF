<?php declare(strict_types=1);

namespace Modules\Common\Models;

use App\Models\AuditLog;
use App\Traits\HasEcosystemAuth;
use App\Traits\HasEcosystemFeatures;
use App\Traits\HasEcosystemMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Throwable;

final class ExclusiveMerch extends Model implements HasMedia
{
    use HasFactory, HasEcosystemFeatures, HasEcosystemAuth, HasEcosystemMedia;
    
        protected $guarded = [];
    
        protected $casts = [
            'points_price' => 'integer',
            'stock_quantity' => 'integer',
            'is_available' => 'boolean',
            'metadata' => 'array',
            'correlation_id' => 'string',
            'tenant_id' => 'string',
            'redeemed_count' => 'integer',
        ];
    
        protected static function booted(): void
        {
            static::creating(function (ExclusiveMerch $model) {
                $model->correlation_id ??= Str::uuid();
                $model->tenant_id ??= Auth::guard('tenant')->id();
                $model->is_available ??= true;
                $model->redeemed_count ??= 0;

                if ($model->points_price === null || $model->points_price <= 0) {
                    throw new \InvalidArgumentException('Merch points_price must be greater than 0');
                }

                if ($model->stock_quantity === null || $model->stock_quantity < 0) {
                    throw new \InvalidArgumentException('Merch stock_quantity cannot be negative');
                }

                Log::channel('merch')->info('ExclusiveMerch creating', [
                    'correlation_id' => $model->correlation_id,
                    'name' => $model->name ?? 'Unknown',
                    'points_price' => $model->points_price,
                    'stock' => $model->stock_quantity,
                ]);
            });

            static::created(function (ExclusiveMerch $model) {
                try {
                    AuditLog::create([
                        'entity_type' => ExclusiveMerch::class,
                        'entity_id' => $model->id,
                        'action' => 'created',
                        'user_id' => Auth::id(),
                        'tenant_id' => $model->tenant_id,
                        'correlation_id' => $model->correlation_id,
                        'changes' => $model->getAttributes(),
                        'metadata' => [
                            'points_price' => $model->points_price,
                            'stock_quantity' => $model->stock_quantity,
                        ],
                    ]);

                    Log::channel('merch')->info('ExclusiveMerch created', [
                        'correlation_id' => $model->correlation_id,
                        'merch_id' => $model->id,
                    ]);
                } catch (Throwable $e) {
                    Log::error('ExclusiveMerch audit creation failed', [
                        'correlation_id' => $model->correlation_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            static::updating(function (ExclusiveMerch $model) {
                $model->correlation_id ??= Str::uuid();

                if ($model->isDirty('points_price') && $model->points_price <= 0) {
                    throw new \InvalidArgumentException('Merch points_price must be greater than 0');
                }

                if ($model->isDirty('stock_quantity') && $model->stock_quantity < 0) {
                    throw new \InvalidArgumentException('Merch stock_quantity cannot be negative');
                }

                Log::channel('merch')->info('ExclusiveMerch updating', [
                    'correlation_id' => $model->correlation_id,
                    'merch_id' => $model->id,
                ]);
            });

            static::updated(function (ExclusiveMerch $model) {
                try {
                    AuditLog::create([
                        'entity_type' => ExclusiveMerch::class,
                        'entity_id' => $model->id,
                        'action' => 'updated',
                        'user_id' => Auth::id(),
                        'tenant_id' => $model->tenant_id,
                        'correlation_id' => $model->correlation_id,
                        'changes' => $model->getChanges(),
                        'metadata' => [
                            'is_available' => $model->is_available,
                            'stock_changed' => $model->isDirty('stock_quantity'),
                            'redeemed_count' => $model->redeemed_count,
                        ],
                    ]);

                    Log::channel('merch')->info('ExclusiveMerch updated', [
                        'correlation_id' => $model->correlation_id,
                        'merch_id' => $model->id,
                    ]);
                } catch (Throwable $e) {
                    Log::error('ExclusiveMerch audit update failed', [
                        'correlation_id' => $model->correlation_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }
    }
    
    class MerchRedemption extends Model
    {
        use HasFactory, HasEcosystemFeatures, HasEcosystemAuth;
    
        protected $guarded = [];
    
        protected $casts = [
            'redeemed_at' => 'datetime',
            'delivery_status' => 'string',
            'correlation_id' => 'string',
            'tenant_id' => 'string',
            'points_spent' => 'integer',
            'metadata' => 'array',
        ];
    
        protected static function booted(): void
        {
            static::creating(function (MerchRedemption $model) {
                $model->correlation_id ??= Str::uuid();
                $model->tenant_id ??= Auth::guard('tenant')->id();
                $model->delivery_status ??= 'pending';
                $model->redeemed_at ??= now();
    
                if ($model->points_spent === null || $model->points_spent <= 0) {
                    throw new \InvalidArgumentException('MerchRedemption points_spent must be greater than 0');
                }
    
                Log::channel('merch')->info('MerchRedemption creating', [
                    'correlation_id' => $model->correlation_id,
                    'user_id' => $model->user_id,
                    'merch_id' => $model->merch_id,
                    'points_spent' => $model->points_spent,
                ]);
            });
    
            static::created(function (MerchRedemption $model) {
                try {
                    AuditLog::create([
                        'entity_type' => MerchRedemption::class,
                        'entity_id' => $model->id,
                        'action' => 'created',
                        'user_id' => Auth::id(),
                        'tenant_id' => $model->tenant_id,
                        'correlation_id' => $model->correlation_id,
                        'changes' => $model->getAttributes(),
                        'metadata' => [
                            'user_id' => $model->user_id,
                            'merch_id' => $model->merch_id,
                            'points_spent' => $model->points_spent,
                            'delivery_status' => $model->delivery_status,
                        ],
                    ]);

                    Log::channel('merch')->info('MerchRedemption created', [
                        'correlation_id' => $model->correlation_id,
                        'redemption_id' => $model->id,
                        'points_spent' => $model->points_spent,
                    ]);
                } catch (Throwable $e) {
                    Log::error('MerchRedemption audit creation failed', [
                        'correlation_id' => $model->correlation_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    
            static::updating(function (MerchRedemption $model) {
                $model->correlation_id ??= Str::uuid();
    
                Log::channel('merch')->info('MerchRedemption updating', [
                    'correlation_id' => $model->correlation_id,
                    'redemption_id' => $model->id,
                ]);
            });
    
            static::updated(function (MerchRedemption $model) {
                try {
                    AuditLog::create([
                        'entity_type' => MerchRedemption::class,
                        'entity_id' => $model->id,
                        'action' => 'updated',
                        'user_id' => Auth::id(),
                        'tenant_id' => $model->tenant_id,
                        'correlation_id' => $model->correlation_id,
                        'changes' => $model->getChanges(),
                        'metadata' => [
                            'delivery_status' => $model->delivery_status,
                        ],
                    ]);

                    Log::channel('merch')->info('MerchRedemption updated', [
                        'correlation_id' => $model->correlation_id,
                        'redemption_id' => $model->id,
                    ]);
                } catch (Throwable $e) {
                    Log::error('MerchRedemption audit update failed', [
                        'correlation_id' => $model->correlation_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }
    
        public function merch()
        {
            return $this->belongsTo(ExclusiveMerch::class);
        }
    
        public function user()
        {
            return $this->belongsTo(\App\Models\User::class);
        }
    }

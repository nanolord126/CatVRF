<?php declare(strict_types=1);

/**
 * WarehouseRentalsService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/warehouserentalsservice
 */


namespace App\Domains\Logistics\WarehouseRentals\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class WarehouseRentalsService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createRental(int $warehouseId,$leaseStart,$leaseEnd,$monthCount,string $correlationId=""):WarehouseRental{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("warehouse:rental:".$this->guard->id(),7))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("warehouse:rental:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($warehouseId, $leaseStart, $leaseEnd, $monthCount, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'warehouse_rental', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$r=WarehouseRental::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'warehouse_id'=>$warehouseId,'tenant_business_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','lease_start'=>$leaseStart,'lease_end'=>$leaseEnd,'tags'=>['warehouse'=>true]]);$this->logger->info('Warehouse rental created',['rental_id'=>$r->id,'correlation_id'=>$correlationId]);return $r;});
    }
    public function completeRental(int $rentalId,string $correlationId=""):WarehouseRental{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($rentalId,$correlationId){$r=WarehouseRental::findOrFail($rentalId);if($r->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$r->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$r->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['rental_id'=>$r->id]);return $r;});}
    public function cancelRental(int $rentalId,string $correlationId=""):WarehouseRental{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($rentalId,$correlationId){$r=WarehouseRental::findOrFail($rentalId);if($r->status==='active')throw new \RuntimeException("Cannot cancel",400);$r->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($r->payment_status==='completed')$this->wallet->credit(tenant()->id,$r->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['rental_id'=>$r->id]);return $r;});}
    public function getRental(int $rentalId):WarehouseRental{return WarehouseRental::findOrFail($rentalId);}
    public function getUserRentals(int $tenantBusinessId){return WarehouseRental::where('tenant_business_id',$tenantBusinessId)->orderBy('created_at','desc')->take(10)->get();}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

<?php declare(strict_types=1);

/**
 * MedicalSuppliesService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/medicalsuppliesservice
 */


namespace App\Domains\Pharmacy\MedicalSupplies\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MedicalSuppliesService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
        public function createOrder(int $pharmacyId,array $items,string $correlationId=""):MedicineOrder{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("med:order:".$this->guard->id(),20))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("med:order:".$this->guard->id(),3600);
            return $this->db->transaction(function() use ($pharmacyId, $items, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'medicine_order', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block'){$this->logger->error('Medicine order blocked',['user_id'=>$this->guard->id(),'correlation_id'=>$correlationId]);throw new \RuntimeException("Security",403);}$o=MedicineOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'pharmacy_id'=>$pharmacyId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','items_json'=>$items,'tags'=>['medical'=>true]]);$this->logger->info('Medicine order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
        }
        public function completeOrder(int $orderId,string $correlationId=""):MedicineOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=MedicineOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);foreach($o->items_json as $i){Medicine::findOrFail($i['medicine_id'])->decrement('stock',$i['quantity']);}$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['order_id'=>$o->id]);return $o;});}
        public function cancelOrder(int $orderId,string $correlationId=""):MedicineOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=MedicineOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed'){$this->wallet->credit(tenant()->id,$o->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['order_id'=>$o->id]);return $o;});}
        public function getOrder(int $orderId):MedicineOrder{return MedicineOrder::findOrFail($orderId);}
        public function getUserOrders(int $clientId){return MedicineOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

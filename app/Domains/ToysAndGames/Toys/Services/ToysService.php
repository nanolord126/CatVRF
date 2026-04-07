<?php declare(strict_types=1);

/**
 * ToysService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/toysservice
 */


namespace App\Domains\ToysAndGames\Toys\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ToysService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
        public function createOrder(int $sellerId,array $items,string $correlationId=""):ToyOrder{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("toys:order:".$this->guard->id(),15))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("toys:order:".$this->guard->id(),3600);
            return $this->db->transaction(function() use ($sellerId, $items, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'toy_order', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block'){$this->logger->error('Toy order blocked',['user_id'=>$this->guard->id(),'correlation_id'=>$correlationId]);throw new \RuntimeException("Security",403);}$o=ToyOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'seller_id'=>$sellerId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','items_json'=>$items,'tags'=>['toys'=>true]]);$this->logger->info('Toy order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
        }
        public function completeOrder(int $orderId,string $correlationId=""):ToyOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=ToyOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);foreach($o->items_json as $i){Toy::findOrFail($i['toy_id'])->decrement('stock',$i['quantity']);}$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['order_id'=>$o->id]);return $o;});}
        public function cancelOrder(int $orderId,string $correlationId=""):ToyOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=ToyOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed'){$this->wallet->credit(tenant()->id,$o->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['order_id'=>$o->id]);return $o;});}
        public function getOrder(int $orderId):ToyOrder{return ToyOrder::findOrFail($orderId);}
        public function getUserOrders(int $clientId){return ToyOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

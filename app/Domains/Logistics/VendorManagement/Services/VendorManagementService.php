<?php declare(strict_types=1);

/**
 * VendorManagementService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/vendormanagementservice
 */


namespace App\Domains\Logistics\VendorManagement\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class VendorManagementService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createOptimization(int $consultantId,$optimizationType,$hoursSpent,$dueDate,string $correlationId=""):VendorOptimization{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("vendor:opt:".$this->guard->id(),9))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("vendor:opt:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($consultantId, $optimizationType, $hoursSpent, $dueDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vendor', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=VendorOptimization::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','optimization_type'=>$optimizationType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['vendor'=>true]]);$this->logger->info('Vendor optimization created',['optimization_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
    }
    public function completeOptimization(int $optimizationId,string $correlationId=""):VendorOptimization{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($optimizationId,$correlationId){$o=VendorOptimization::findOrFail($optimizationId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['optimization_id'=>$o->id]);return $o;});}
    public function cancelOptimization(int $optimizationId,string $correlationId=""):VendorOptimization{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($optimizationId,$correlationId){$o=VendorOptimization::findOrFail($optimizationId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['optimization_id'=>$o->id]);return $o;});}
    public function getOptimization(int $optimizationId):VendorOptimization{return VendorOptimization::findOrFail($optimizationId);}
    public function getUserOptimizations(int $clientId){return VendorOptimization::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

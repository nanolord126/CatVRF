<?php declare(strict_types=1);

/**
 * SupplierRelationshipService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/supplierrelationshipservice
 */


namespace App\Domains\Logistics\SupplierRelationship\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class SupplierRelationshipService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createEngagement(int $advisorId,$engagementType,$hoursSpent,$dueDate,string $correlationId=""):SRMEngagement{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("srm:eng:".$this->guard->id(),16))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("srm:eng:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($advisorId, $engagementType, $hoursSpent, $dueDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'srm', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=SRMEngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'advisor_id'=>$advisorId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','engagement_type'=>$engagementType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['srm'=>true]]);$this->logger->info('SRM engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
    }
    public function completeEngagement(int $engagementId,string $correlationId=""):SRMEngagement{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($engagementId,$correlationId){$e=SRMEngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);return $e;});}
    public function cancelEngagement(int $engagementId,string $correlationId=""):SRMEngagement{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($engagementId,$correlationId){$e=SRMEngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);return $e;});}
    public function getEngagement(int $engagementId):SRMEngagement{return SRMEngagement::findOrFail($engagementId);}
    public function getUserEngagements(int $clientId){return SRMEngagement::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

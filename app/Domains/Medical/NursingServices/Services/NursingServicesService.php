<?php declare(strict_types=1);

/**
 * NursingServicesService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/nursingservicesservice
 */


namespace App\Domains\Medical\NursingServices\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class NursingServicesService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createEngagement(int $agencyId,$careType,$hoursRequired,$startDate,$endDate,string $correlationId=""):NursingEngagement{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("nurs:eng:".$this->guard->id(),9))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("nurs:eng:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($agencyId, $careType, $hoursRequired, $startDate, $endDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'nursing', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=NursingEngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'agency_id'=>$agencyId,'patient_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','care_type'=>$careType,'hours_required'=>$hoursRequired,'start_date'=>$startDate,'end_date'=>$endDate,'tags'=>['nursing'=>true]]);$this->logger->info('Nursing engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
    }
    public function completeEngagement(int $engagementId,string $correlationId=""):NursingEngagement{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($engagementId,$correlationId){$e=NursingEngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);return $e;});}
    public function cancelEngagement(int $engagementId,string $correlationId=""):NursingEngagement{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($engagementId,$correlationId){$e=NursingEngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);return $e;});}
    public function getEngagement(int $engagementId):NursingEngagement{return NursingEngagement::findOrFail($engagementId);}
    public function getUserEngagements(int $patientId){return NursingEngagement::where('patient_id',$patientId)->orderBy('created_at','desc')->take(10)->get();}

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

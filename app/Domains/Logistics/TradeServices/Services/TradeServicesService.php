<?php declare(strict_types=1);

/**
 * TradeServicesService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/tradeservicesservice
 */


namespace App\Domains\Logistics\TradeServices\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TradeServicesService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createJob(int $tradepersonId,$jobDate,$durationHours,$jobType,string $correlationId=""):TradeJob{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("trade:job:".$this->guard->id(),15))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("trade:job:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($tradepersonId, $jobDate, $durationHours, $jobType, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'trade_job', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$j=TradeJob::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'tradesperson_id'=>$tradepersonId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','job_date'=>$jobDate,'duration_hours'=>$durationHours,'job_type'=>$jobType,'tags'=>['trade'=>true]]);$this->logger->info('Trade job created',['job_id'=>$j->id,'correlation_id'=>$correlationId]);return $j;});
    }
    public function completeJob(int $jobId,string $correlationId=""):TradeJob{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($jobId,$correlationId){$j=TradeJob::findOrFail($jobId);if($j->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$j->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$j->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,'job_id'=>$j->id]);return $j;});}
    public function cancelJob(int $jobId,string $correlationId=""):TradeJob{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($jobId,$correlationId){$j=TradeJob::findOrFail($jobId);if($j->status==='completed')throw new \RuntimeException("Cannot cancel",400);$j->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($j->payment_status==='completed')$this->wallet->credit(tenant()->id,$j->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,'job_id'=>$j->id]);return $j;});}
    public function getJob(int $jobId):TradeJob{return TradeJob::findOrFail($jobId);}
    public function getUserJobs(int $clientId){return TradeJob::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

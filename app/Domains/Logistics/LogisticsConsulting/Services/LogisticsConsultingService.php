<?php declare(strict_types=1);

/**
 * LogisticsConsultingService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logisticsconsultingservice
 */


namespace App\Domains\Logistics\LogisticsConsulting\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class LogisticsConsultingService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createProject(int $consultantId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):LogisticsProject{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("log:proj:".$this->guard->id(),9))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("log:proj:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($consultantId, $projectType, $hoursSpent, $dueDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'logistics', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=LogisticsProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['logistics'=>true]]);$this->logger->info('Logistics project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):LogisticsProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=LogisticsProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):LogisticsProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=LogisticsProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):LogisticsProject{return LogisticsProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return LogisticsProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

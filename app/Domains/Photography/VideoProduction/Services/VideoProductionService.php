<?php declare(strict_types=1);

/**
 * VideoProductionService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/videoproductionservice
 */


namespace App\Domains\Photography\VideoProduction\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class VideoProductionService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createProject(int $producerId,$projectType,$productionHours,$dueDate,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("video:project:".$this->guard->id(),8))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("video:project:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($producerId, $projectType, $productionHours, $dueDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'video_project', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$v=VideoProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'producer_id'=>$producerId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'production_hours'=>$productionHours,'due_date'=>$dueDate,'tags'=>['video'=>true]]);$this->logger->info('Video project created',['project_id'=>$v->id,'correlation_id'=>$correlationId]);return $v;});
    }
    public function completeProject(int $projectId,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$v=VideoProject::findOrFail($projectId);if($v->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$v->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$v->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['project_id'=>$v->id]);return $v;});}
    public function cancelProject(int $projectId,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$v=VideoProject::findOrFail($projectId);if($v->status==='completed')throw new \RuntimeException("Cannot cancel",400);$v->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($v->payment_status==='completed')$this->wallet->credit(tenant()->id,$v->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['project_id'=>$v->id]);return $v;});}
    public function getProject(int $projectId):VideoProject{return VideoProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return VideoProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

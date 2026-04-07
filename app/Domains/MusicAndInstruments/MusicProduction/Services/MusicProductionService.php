<?php declare(strict_types=1);

/**
 * MusicProductionService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/musicproductionservice
 */


namespace App\Domains\MusicAndInstruments\MusicProduction\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MusicProductionService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createProject(int $producerId,$projectType,$productionHours,$dueDate,string $correlationId=""):MusicProject{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("music:project:".$this->guard->id(),5))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("music:project:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($producerId, $projectType, $productionHours, $dueDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'music_prod', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$m=MusicProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'producer_id'=>$producerId,'artist_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'production_hours'=>$productionHours,'due_date'=>$dueDate,'tags'=>['music'=>true]]);$this->logger->info('Music project created',['project_id'=>$m->id,'correlation_id'=>$correlationId]);return $m;});
    }
    public function completeProject(int $projectId,string $correlationId=""):MusicProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$m=MusicProject::findOrFail($projectId);if($m->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$m->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$m->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['project_id'=>$m->id]);return $m;});}
    public function cancelProject(int $projectId,string $correlationId=""):MusicProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$m=MusicProject::findOrFail($projectId);if($m->status==='completed')throw new \RuntimeException("Cannot cancel",400);$m->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($m->payment_status==='completed')$this->wallet->credit(tenant()->id,$m->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['project_id'=>$m->id]);return $m;});}
    public function getProject(int $projectId):MusicProject{return MusicProject::findOrFail($projectId);}
    public function getUserProjects(int $artistId){return MusicProject::where('artist_id',$artistId)->orderBy('created_at','desc')->take(10)->get();}

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

<?php declare(strict_types=1);

/**
 * PersonalTrainingService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/personaltrainingservice
 */


namespace App\Domains\Sports\PersonalTraining\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PersonalTrainingService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createSession(int $trainerId,$workoutType,$sessionHours,$sessionDate,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("train:sess:".$this->guard->id(),15))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("train:sess:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($trainerId, $workoutType, $sessionHours, $sessionDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'personal_training', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=TrainingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'trainer_id'=>$trainerId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','workout_type'=>$workoutType,'session_hours'=>$sessionHours,'session_date'=>$sessionDate,'tags'=>['training'=>true]]);$this->logger->info('Training session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
    }
    public function completeSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=TrainingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['session_id'=>$s->id]);return $s;});}
    public function cancelSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=TrainingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['session_id'=>$s->id]);return $s;});}
    public function getSession(int $sessionId):TrainingSession{return TrainingSession->findOrFail($sessionId);}
    public function getUserSessions(int $clientId){return TrainingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

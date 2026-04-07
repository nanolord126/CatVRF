<?php declare(strict_types=1);

/**
 * LeadershipDevelopmentService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/leadershipdevelopmentservice
 */


namespace App\Domains\PersonalDevelopment\LeadershipDevelopment\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class LeadershipDevelopmentService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createProgram(int $mentorId,$programType,$hoursSpent,$dueDate,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("leader:prog:".$this->guard->id(),11))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("leader:prog:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($mentorId, $programType, $hoursSpent, $dueDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'leader', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=MentorshipProgram::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'mentor_id'=>$mentorId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','program_type'=>$programType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['leader'=>true]]);$this->logger->info('Mentorship program created',['program_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProgram(int $programId,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=MentorshipProgram::findOrFail($programId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['program_id'=>$p->id]);return $p;});}
    public function cancelProgram(int $programId,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=MentorshipProgram::findOrFail($programId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['program_id'=>$p->id]);return $p;});}
    public function getProgram(int $programId):MentorshipProgram{return MentorshipProgram::findOrFail($programId);}
    public function getUserPrograms(int $clientId){return MentorshipProgram::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

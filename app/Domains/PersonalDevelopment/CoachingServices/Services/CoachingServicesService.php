<?php declare(strict_types=1);

/**
 * CoachingServicesService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/coachingservicesservice
 */


namespace App\Domains\PersonalDevelopment\CoachingServices\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class CoachingServicesService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createProgram(int $coachId,$programType,$coachingHours,$dueDate,string $correlationId=""):CoachingProgram{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("coach:prog:".$this->guard->id(),18))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("coach:prog:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($coachId, $programType, $coachingHours, $dueDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'coaching', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=CoachingProgram::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'coach_id'=>$coachId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','program_type'=>$programType,'coaching_hours'=>$coachingHours,'due_date'=>$dueDate,'tags'=>['coaching'=>true]]);$this->logger->info('Coaching program created',['program_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProgram(int $programId,string $correlationId=""):CoachingProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=CoachingProgram::findOrFail($programId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['program_id'=>$p->id]);return $p;});}
    public function cancelProgram(int $programId,string $correlationId=""):CoachingProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=CoachingProgram::findOrFail($programId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['program_id'=>$p->id]);return $p;});}
    public function getProgram(int $programId):CoachingProgram{return CoachingProgram::findOrFail($programId);}
    public function getUserPrograms(int $clientId){return CoachingProgram::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

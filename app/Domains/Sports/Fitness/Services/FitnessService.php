<?php declare(strict_types=1);

/**
 * FitnessService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fitnessservice
 */


namespace App\Domains\Sports\Fitness\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class FitnessService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createMembership(int $gymId,$membershipType,$monthCount,string $correlationId=""):FitnessMembership{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("fitness:member:".$this->guard->id(),8))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("fitness:member:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($gymId, $membershipType, $monthCount, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'fitness_member', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$m=FitnessMembership::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'gym_id'=>$gymId,'member_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','membership_type'=>$membershipType,'start_date'=>now(),'end_date'=>now()->addMonths($monthCount),'tags'=>['fitness'=>true]]);$this->logger->info('Fitness membership created',['membership_id'=>$m->id,'correlation_id'=>$correlationId]);return $m;});
    }
    public function completeMembership(int $membershipId,string $correlationId=""):FitnessMembership{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($membershipId,$correlationId){$m=FitnessMembership::findOrFail($membershipId);if($m->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$m->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$m->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['membership_id'=>$m->id]);return $m;});}
    public function cancelMembership(int $membershipId,string $correlationId=""):FitnessMembership{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($membershipId,$correlationId){$m=FitnessMembership::findOrFail($membershipId);if($m->status==='active')throw new \RuntimeException("Cannot cancel",400);$m->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($m->payment_status==='completed')$this->wallet->credit(tenant()->id,$m->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['membership_id'=>$m->id]);return $m;});}
    public function getMembership(int $membershipId):FitnessMembership{return FitnessMembership::findOrFail($membershipId);}
    public function getUserMemberships(int $memberId){return FitnessMembership::where('member_id',$memberId)->orderBy('created_at','desc')->take(10)->get();}

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

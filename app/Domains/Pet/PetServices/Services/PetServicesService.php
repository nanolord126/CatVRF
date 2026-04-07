<?php declare(strict_types=1);

/**
 * PetServicesService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/petservicesservice
 */


namespace App\Domains\Pet\PetServices\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PetServicesService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createSession(int $groomerId,$sessionDate,$durationHours,$petType,string $correlationId=""):PetGroomingSession{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("pet:service:".$this->guard->id(),20))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("pet:service:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($groomerId, $sessionDate, $durationHours, $petType, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'pet_service', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=PetGroomingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'groomer_id'=>$groomerId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'pet_type'=>$petType,'tags'=>['pet_service'=>true]]);$this->logger->info('Pet service booked',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
    }
    public function completeSession(int $sessionId,string $correlationId=""):PetGroomingSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=PetGroomingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,'session_id'=>$s->id]);return $s;});}
    public function cancelSession(int $sessionId,string $correlationId=""):PetGroomingSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=PetGroomingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,'session_id'=>$s->id]);return $s;});}
    public function getSession(int $sessionId):PetGroomingSession{return PetGroomingSession->findOrFail($sessionId);}
    public function getUserSessions(int $clientId){return PetGroomingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

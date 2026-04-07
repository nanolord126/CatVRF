<?php declare(strict_types=1);

/**
 * DanceStudiosService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/dancestudiosservice
 */


namespace App\Domains\Sports\DanceStudios\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class DanceStudiosService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createClass(int $studioId,$classDate,string $correlationId=""):DanceClass{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("dance:class:".$this->guard->id(),30))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("dance:class:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($studioId, $classDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'dance_class', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=DanceClass::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'studio_id'=>$studioId,'student_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$s->price_kopecks_per_class,'payout_kopecks'=>$s->price_kopecks_per_class-(int)($s->price_kopecks_per_class*0.14),'payment_status'=>'pending','class_date'=>$classDate,'tags'=>['dance'=>true]]);$this->logger->info('Dance class booked',['class_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
    }
    public function completeClass(int $classId,string $correlationId=""):DanceClass{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($classId,$correlationId){$c=DanceClass::findOrFail($classId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,'class_id'=>$c->id]);return $c;});}
    public function cancelClass(int $classId,string $correlationId=""):DanceClass{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($classId,$correlationId){$c=DanceClass::findOrFail($classId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,'class_id'=>$c->id]);return $c;});}
    public function getClass(int $classId):DanceClass{return DanceClass::findOrFail($classId);}
    public function getUserClasses(int $studentId){return DanceClass::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}

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

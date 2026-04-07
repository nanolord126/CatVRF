<?php declare(strict_types=1);

/**
 * PetSittingService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/petsittingservice
 */


namespace App\Domains\Pet\PetSitting\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PetSittingService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createBooking(int $sitterId,$startDate,$endDate,$petNames,string $correlationId=""):SittingBooking{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("petsit:book:".$this->guard->id(),14))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("petsit:book:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($sitterId, $startDate, $endDate, $petNames, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'petsit_booking', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=SittingBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'sitter_id'=>$sitterId,'owner_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','start_date'=>$startDate,'end_date'=>$endDate,'pet_names'=>$petNames,'tags'=>['petsit'=>true]]);$this->logger->info('Pet sitting booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
    }
    public function completeBooking(int $bookingId,string $correlationId=""):SittingBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=SittingBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,'booking_id'=>$b->id]);return $b;});}
    public function cancelBooking(int $bookingId,string $correlationId=""):SittingBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=SittingBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,'booking_id'=>$b->id]);return $b;});}
    public function getBooking(int $bookingId):SittingBooking{return SittingBooking::findOrFail($bookingId);}
    public function getUserBookings(int $ownerId){return SittingBooking::where('owner_id',$ownerId)->orderBy('created_at','desc')->take(10)->get();}

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

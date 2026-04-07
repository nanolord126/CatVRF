<?php declare(strict_types=1);

/**
 * BilliardsService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/billiardsservice
 */


namespace App\Domains\Sports\Billiards\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class BilliardsService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createBooking(int $hallId,$bookingDate,$durationHours,$tableNumber,string $correlationId=""):BilliardBooking{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("billiards:book:".$this->guard->id(),20))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("billiards:book:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($hallId, $bookingDate, $durationHours, $tableNumber, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'billiard_booking', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=BilliardBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'hall_id'=>$hallId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','booking_date'=>$bookingDate,'duration_hours'=>$durationHours,'table_number'=>$tableNumber,'tags'=>['billiards'=>true]]);$this->logger->info('Billiard booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
    }
    public function completeBooking(int $bookingId,string $correlationId=""):BilliardBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=BilliardBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['booking_id'=>$b->id]);return $b;});}
    public function cancelBooking(int $bookingId,string $correlationId=""):BilliardBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=BilliardBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['booking_id'=>$b->id]);return $b;});}
    public function getBooking(int $bookingId):BilliardBooking{return BilliardBooking::findOrFail($bookingId);}
    public function getUserBookings(int $clientId){return BilliardBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

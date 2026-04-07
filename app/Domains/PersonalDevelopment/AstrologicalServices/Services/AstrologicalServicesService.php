<?php declare(strict_types=1);

/**
 * AstrologicalServicesService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/astrologicalservicesservice
 */


namespace App\Domains\PersonalDevelopment\AstrologicalServices\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class AstrologicalServicesService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createReading(int $astrologerId,$readingType,$readingHours,$readingDate,string $correlationId=""):AstrologyReading{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("astro:read:".$this->guard->id(),12))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("astro:read:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($astrologerId, $readingType, $readingHours, $readingDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'astrology', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$r=AstrologyReading::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'astrologer_id'=>$astrologerId,'client_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','reading_type'=>$readingType,'reading_hours'=>$readingHours,'reading_date'=>$readingDate,'tags'=>['astrology'=>true]]);$this->logger->info('Astrology reading created',['reading_id'=>$r->id,'correlation_id'=>$correlationId]);return $r;});
    }
    public function completeReading(int $readingId,string $correlationId=""):AstrologyReading{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($readingId,$correlationId){$r=AstrologyReading::findOrFail($readingId);if($r->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$r->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$r->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,'reading_id'=>$r->id]);return $r;});}
    public function cancelReading(int $readingId,string $correlationId=""):AstrologyReading{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($readingId,$correlationId){$r=AstrologyReading::findOrFail($readingId);if($r->status==='completed')throw new \RuntimeException("Cannot cancel",400);$r->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($r->payment_status==='completed')$this->wallet->credit(tenant()->id,$r->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,'reading_id'=>$r->id]);return $r;});}
    public function getReading(int $readingId):AstrologyReading{return AstrologyReading::findOrFail($readingId);}
    public function getUserReadings(int $clientId){return AstrologyReading::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}

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

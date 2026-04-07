<?php declare(strict_types=1);

/**
 * MusicalInstrumentsService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/musicalinstrumentsservice
 */


namespace App\Domains\MusicAndInstruments\MusicalInstruments\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MusicalInstrumentsService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createLesson(int $teacherId,$instrument,$lessonHours,$lessonDate,string $correlationId=""):InstrumentLesson{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("instr:lesson:".$this->guard->id(),16))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("instr:lesson:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($teacherId, $instrument, $lessonHours, $lessonDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'instrument_lesson', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$l=InstrumentLesson::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'teacher_id'=>$teacherId,'student_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','instrument'=>$instrument,'lesson_hours'=>$lessonHours,'lesson_date'=>$lessonDate,'tags'=>['instrument'=>true]]);$this->logger->info('Instrument lesson created',['lesson_id'=>$l->id,'correlation_id'=>$correlationId]);return $l;});
    }
    public function completeLesson(int $lessonId,string $correlationId=""):InstrumentLesson{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($lessonId,$correlationId){$l=InstrumentLesson::findOrFail($lessonId);if($l->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$l->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$l->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['lesson_id'=>$l->id]);return $l;});}
    public function cancelLesson(int $lessonId,string $correlationId=""):InstrumentLesson{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($lessonId,$correlationId){$l=InstrumentLesson::findOrFail($lessonId);if($l->status==='completed')throw new \RuntimeException("Cannot cancel",400);$l->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($l->payment_status==='completed')$this->wallet->credit(tenant()->id,$l->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['lesson_id'=>$l->id]);return $l;});}
    public function getLesson(int $lessonId):InstrumentLesson{return InstrumentLesson::findOrFail($lessonId);}
    public function getUserLessons(int $studentId){return InstrumentLesson::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}

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

<?php declare(strict_types=1);

/**
 * DanceInstructorService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/danceinstructorservice
 */


namespace App\Domains\Sports\DanceInstructor\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class DanceInstructorService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createLesson(int $teacherId,$danceStyle,$lessonHours,$lessonDate,string $correlationId=""):DanceLesson{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("dance:lesson:".$this->guard->id(),23))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("dance:lesson:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($teacherId, $danceStyle, $lessonHours, $lessonDate, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'dance_lesson', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$l=DanceLesson::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'teacher_id'=>$teacherId,'student_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','dance_style'=>$danceStyle,'lesson_hours'=>$lessonHours,'lesson_date'=>$lessonDate,'tags'=>['dance'=>true]]);$this->logger->info('Dance lesson created',['lesson_id'=>$l->id,'correlation_id'=>$correlationId]);return $l;});
    }
    public function completeLesson(int $lessonId,string $correlationId=""):DanceLesson{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($lessonId,$correlationId){$l=DanceLesson::findOrFail($lessonId);if($l->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$l->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$l->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['lesson_id'=>$l->id]);return $l;});}
    public function cancelLesson(int $lessonId,string $correlationId=""):DanceLesson{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($lessonId,$correlationId){$l=DanceLesson::findOrFail($lessonId);if($l->status==='completed')throw new \RuntimeException("Cannot cancel",400);$l->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($l->payment_status==='completed')$this->wallet->credit(tenant()->id,$l->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['lesson_id'=>$l->id]);return $l;});}
    public function getLesson(int $lessonId):DanceLesson{return DanceLesson::findOrFail($lessonId);}
    public function getUserLessons(int $studentId){return DanceLesson::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}

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

<?php declare(strict_types=1);

namespace App\Domains\Education\AcademicTutoring\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AcademicTutoringService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createSession(int $tutorId,$subject,$sessionHours,$dueDate,string $correlationId=""):TutorSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("tut:sess:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("tut:sess:".auth()->id(),3600);
    return DB::transaction(function()use($tutorId,$subject,$sessionHours,$dueDate,$correlationId){$t=Tutor::findOrFail($tutorId);$total=(int)($t->price_kopecks_per_hour*$sessionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'tutoring','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=TutorSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'tutor_id'=>$tutorId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','subject'=>$subject,'session_hours'=>$sessionHours,'due_date'=>$dueDate,'tags'=>['tutoring'=>true]]);Log::channel('audit')->info('Tutor session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
    }
    public function completeSession(int $sessionId,string $correlationId=""):TutorSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TutorSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'tutor_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Tutor session completed',['session_id'=>$s->id]);return $s;});}
    public function cancelSession(int $sessionId,string $correlationId=""):TutorSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TutorSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'tutor_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Tutor session cancelled',['session_id'=>$s->id]);return $s;});}
    public function getSession(int $sessionId):TutorSession{return TutorSession->findOrFail($sessionId);}
    public function getUserSessions(int $studentId){return TutorSession->where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);
namespace App\Domains\LanguageTutoring\Services;
use App\Domains\LanguageTutoring\Models\LanguageTutor;
use App\Domains\LanguageTutoring\Models\TutoringSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class LanguageTutoringService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createSession(int $tutorId,$sessionDate,$durationHours,$language,string $correlationId=""):TutoringSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("tutor:session:".auth()->id(),28))throw new \RuntimeException("Too many",429);RateLimiter::hit("tutor:session:".auth()->id(),3600);
return DB::transaction(function()use($tutorId,$sessionDate,$durationHours,$language,$correlationId){$t=LanguageTutor::findOrFail($tutorId);$total=(int)($t->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'tutoring','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=TutoringSession::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'tutor_id'=>$tutorId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'language'=>$language,'tags'=>['tutoring'=>true]]);Log::channel('audit')->info('Tutoring session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):TutoringSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TutoringSession::findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'tutoring_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Tutoring session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):TutoringSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TutoringSession::findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'tutoring_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Tutoring session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):TutoringSession{return TutoringSession::findOrFail($sessionId);}
public function getUserSessions(int $studentId){return TutoringSession::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\AcademicTutoring\Services;
use App\Domains\AcademicTutoring\Models\Tutor;
use App\Domains\AcademicTutoring\Models\TutorSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * AcademicTutoringService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AcademicTutoringService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createSession(int $tutorId,$subject,$sessionHours,$dueDate,string $correlationId=""):TutorSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("tut:sess:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("tut:sess:".auth()->id(),3600);
return $this->db->transaction(function()use($tutorId,$subject,$sessionHours,$dueDate,$correlationId){$t=Tutor::findOrFail($tutorId);$total=(int)($t->price_kopecks_per_hour*$sessionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'tutoring','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=Tutor$this->session->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'tutor_id'=>$tutorId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','subject'=>$subject,'session_hours'=>$sessionHours,'due_date'=>$dueDate,'tags'=>['tutoring'=>true]]);$this->log->channel('audit')->info('Tutor session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):TutorSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=Tutor$this->session->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'tutor_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);$this->log->channel('audit')->info('Tutor session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):TutorSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=Tutor$this->session->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'tutor_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);$this->log->channel('audit')->info('Tutor session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):TutorSession{return Tutor$this->session->findOrFail($sessionId);}
public function getUserSessions(int $studentId){return Tutor$this->session->where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

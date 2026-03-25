declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\LeadershipDevelopment\Services;
use App\Domains\LeadershipDevelopment\Models\LeadershipMentor;
use App\Domains\LeadershipDevelopment\Models\MentorshipProgram;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * LeadershipDevelopmentService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class LeadershipDevelopmentService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProgram(int $mentorId,$programType,$hoursSpent,$dueDate,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("leader:prog:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("leader:prog:".auth()->id(),3600);
return $this->db->transaction(function()use($mentorId,$programType,$hoursSpent,$dueDate,$correlationId){$m=LeadershipMentor::findOrFail($mentorId);$total=(int)($m->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'leader','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=MentorshipProgram::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'mentor_id'=>$mentorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','program_type'=>$programType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['leader'=>true]]);$this->log->channel('audit')->info('Mentorship program created',['program_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProgram(int $programId,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=MentorshipProgram::findOrFail($programId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'leader_payout',['correlation_id'=>$correlationId,'program_id'=>$p->id]);$this->log->channel('audit')->info('Mentorship program completed',['program_id'=>$p->id]);return $p;});}
public function cancelProgram(int $programId,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=MentorshipProgram::findOrFail($programId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'leader_refund',['correlation_id'=>$correlationId,'program_id'=>$p->id]);$this->log->channel('audit')->info('Mentorship program cancelled',['program_id'=>$p->id]);return $p;});}
public function getProgram(int $programId):MentorshipProgram{return MentorshipProgram::findOrFail($programId);}
public function getUserPrograms(int $clientId){return MentorshipProgram::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

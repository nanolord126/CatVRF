declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\CoachingServices\Services;
use App\Domains\CoachingServices\Models\Coach;
use App\Domains\CoachingServices\Models\CoachingProgram;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * CoachingServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CoachingServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProgram(int $coachId,$programType,$coachingHours,$dueDate,string $correlationId=""):CoachingProgram{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("coach:prog:".auth()->id(),18))throw new \RuntimeException("Too many",429);RateLimiter::hit("coach:prog:".auth()->id(),3600);
return $this->db->transaction(function()use($coachId,$programType,$coachingHours,$dueDate,$correlationId){$c=Coach::findOrFail($coachId);$total=(int)($c->price_kopecks_per_hour*$coachingHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'coaching','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=CoachingProgram::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'coach_id'=>$coachId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','program_type'=>$programType,'coaching_hours'=>$coachingHours,'due_date'=>$dueDate,'tags'=>['coaching'=>true]]);$this->log->channel('audit')->info('Coaching program created',['program_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProgram(int $programId,string $correlationId=""):CoachingProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=CoachingProgram::findOrFail($programId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'coach_payout',['correlation_id'=>$correlationId,'program_id'=>$p->id]);$this->log->channel('audit')->info('Coaching program completed',['program_id'=>$p->id]);return $p;});}
public function cancelProgram(int $programId,string $correlationId=""):CoachingProgram{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($programId,$correlationId){$p=CoachingProgram::findOrFail($programId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'coach_refund',['correlation_id'=>$correlationId,'program_id'=>$p->id]);$this->log->channel('audit')->info('Coaching program cancelled',['program_id'=>$p->id]);return $p;});}
public function getProgram(int $programId):CoachingProgram{return CoachingProgram::findOrFail($programId);}
public function getUserPrograms(int $clientId){return CoachingProgram::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

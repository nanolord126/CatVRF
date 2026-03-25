declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\BeautyServices\Services;
use App\Domains\BeautyServices\Models\BeautyStudio;
use App\Domains\BeautyServices\Models\BeautyService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * BeautyServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BeautyServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createAppointment(int $studioId,$serviceType,$durationMinutes,$appointmentDate,string $correlationId=""):BeautyService{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("beauty:appt:".auth()->id(),18))throw new \RuntimeException("Too many",429);RateLimiter::hit("beauty:appt:".auth()->id(),3600);
return $this->db->transaction(function()use($studioId,$serviceType,$durationMinutes,$appointmentDate,$correlationId){$s=BeautyStudio::findOrFail($studioId);$total=(int)($s->price_kopecks_per_minute*$durationMinutes);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'beauty_service','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$a=BeautyService::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'studio_id'=>$studioId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','service_type'=>$serviceType,'duration_minutes'=>$durationMinutes,'appointment_date'=>$appointmentDate,'tags'=>['beauty'=>true]]);$this->log->channel('audit')->info('Beauty service appointment created',['appointment_id'=>$a->id,'correlation_id'=>$correlationId]);return $a;});
}
public function completeAppointment(int $appointmentId,string $correlationId=""):BeautyService{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($appointmentId,$correlationId){$a=BeautyService::findOrFail($appointmentId);if($a->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$a->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$a->payout_kopecks,'beauty_payout',['correlation_id'=>$correlationId,'appointment_id'=>$a->id]);$this->log->channel('audit')->info('Beauty service appointment completed',['appointment_id'=>$a->id]);return $a;});}
public function cancelAppointment(int $appointmentId,string $correlationId=""):BeautyService{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($appointmentId,$correlationId){$a=BeautyService::findOrFail($appointmentId);if($a->status==='completed')throw new \RuntimeException("Cannot cancel",400);$a->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($a->payment_status==='completed')$this->wallet->credit(tenant()->id,$a->total_kopecks,'beauty_refund',['correlation_id'=>$correlationId,'appointment_id'=>$a->id]);$this->log->channel('audit')->info('Beauty service appointment cancelled',['appointment_id'=>$a->id]);return $a;});}
public function getAppointment(int $appointmentId):BeautyService{return BeautyService::findOrFail($appointmentId);}
public function getUserAppointments(int $clientId){return BeautyService::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

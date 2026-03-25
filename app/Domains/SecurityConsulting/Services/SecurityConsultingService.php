declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\SecurityConsulting\Services;
use App\Domains\SecurityConsulting\Models\SecurityConsultant;
use App\Domains\SecurityConsulting\Models\SecurityAudit;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * SecurityConsultingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SecurityConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createAudit(int $consultantId,$auditType,$consultationHours,$dueDate,string $correlationId=""):SecurityAudit{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("sec:audit:".auth()->id(),4))throw new \RuntimeException("Too many",429);RateLimiter::hit("sec:audit:".auth()->id(),3600);
return $this->db->transaction(function()use($consultantId,$auditType,$consultationHours,$dueDate,$correlationId){$c=SecurityConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'security_audit','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$a=SecurityAudit::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','audit_type'=>$auditType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['security'=>true]]);$this->log->channel('audit')->info('Security audit created',['audit_id'=>$a->id,'correlation_id'=>$correlationId]);return $a;});
}
public function completeAudit(int $auditId,string $correlationId=""):SecurityAudit{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($auditId,$correlationId){$a=SecurityAudit::findOrFail($auditId);if($a->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$a->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$a->payout_kopecks,'sec_payout',['correlation_id'=>$correlationId,'audit_id'=>$a->id]);$this->log->channel('audit')->info('Security audit completed',['audit_id'=>$a->id]);return $a;});}
public function cancelAudit(int $auditId,string $correlationId=""):SecurityAudit{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($auditId,$correlationId){$a=SecurityAudit::findOrFail($auditId);if($a->status==='completed')throw new \RuntimeException("Cannot cancel",400);$a->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($a->payment_status==='completed')$this->wallet->credit(tenant()->id,$a->total_kopecks,'sec_refund',['correlation_id'=>$correlationId,'audit_id'=>$a->id]);$this->log->channel('audit')->info('Security audit cancelled',['audit_id'=>$a->id]);return $a;});}
public function getAudit(int $auditId):SecurityAudit{return SecurityAudit::findOrFail($auditId);}
public function getUserAudits(int $clientId){return SecurityAudit::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

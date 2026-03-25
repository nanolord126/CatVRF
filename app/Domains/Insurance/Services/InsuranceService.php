declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\Insurance\Services;
use App\Domains\Insurance\Models\InsuranceCompany;
use App\Domains\Insurance\Models\InsurancePolicy;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * InsuranceService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class InsuranceService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createPolicy(int $companyId,$policyType,$coverageAmount,$durationMonths,string $correlationId=""):InsurancePolicy{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("insurance:policy:".auth()->id(),5))throw new \RuntimeException("Too many",429);RateLimiter::hit("insurance:policy:".auth()->id(),3600);
return $this->db->transaction(function()use($companyId,$policyType,$coverageAmount,$durationMonths,$correlationId){$c=InsuranceCompany::findOrFail($companyId);$total=(int)(($coverageAmount/100)*$durationMonths);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'insurance_policy','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=InsurancePolicy::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'company_id'=>$companyId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','policy_type'=>$policyType,'coverage_amount'=>$coverageAmount,'duration_months'=>$durationMonths,'tags'=>['insurance'=>true]]);$this->log->channel('audit')->info('Insurance policy created',['policy_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completePolicy(int $policyId,string $correlationId=""):InsurancePolicy{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($policyId,$correlationId){$p=InsurancePolicy::findOrFail($policyId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'insurance_payout',['correlation_id'=>$correlationId,'policy_id'=>$p->id]);$this->log->channel('audit')->info('Insurance policy activated',['policy_id'=>$p->id]);return $p;});}
public function cancelPolicy(int $policyId,string $correlationId=""):InsurancePolicy{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($policyId,$correlationId){$p=InsurancePolicy::findOrFail($policyId);if($p->status==='active')throw new \RuntimeException("Cannot cancel active",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'insurance_refund',['correlation_id'=>$correlationId,'policy_id'=>$p->id]);$this->log->channel('audit')->info('Insurance policy cancelled',['policy_id'=>$p->id]);return $p;});}
public function getPolicy(int $policyId):InsurancePolicy{return InsurancePolicy::findOrFail($policyId);}
public function getUserPolicies(int $clientId){return InsurancePolicy::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

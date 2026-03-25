declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\VendorManagement\Services;
use App\Domains\VendorManagement\Models\VendorConsultant;
use App\Domains\VendorManagement\Models\VendorOptimization;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * VendorManagementService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class VendorManagementService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createOptimization(int $consultantId,$optimizationType,$hoursSpent,$dueDate,string $correlationId=""):VendorOptimization{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("vendor:opt:".auth()->id(),9))throw new \RuntimeException("Too many",429);RateLimiter::hit("vendor:opt:".auth()->id(),3600);
return $this->db->transaction(function()use($consultantId,$optimizationType,$hoursSpent,$dueDate,$correlationId){$c=VendorConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'vendor','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=VendorOptimization::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','optimization_type'=>$optimizationType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['vendor'=>true]]);$this->log->channel('audit')->info('Vendor optimization created',['optimization_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOptimization(int $optimizationId,string $correlationId=""):VendorOptimization{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($optimizationId,$correlationId){$o=VendorOptimization::findOrFail($optimizationId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'vendor_payout',['correlation_id'=>$correlationId,'optimization_id'=>$o->id]);$this->log->channel('audit')->info('Vendor optimization completed',['optimization_id'=>$o->id]);return $o;});}
public function cancelOptimization(int $optimizationId,string $correlationId=""):VendorOptimization{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($optimizationId,$correlationId){$o=VendorOptimization::findOrFail($optimizationId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'vendor_refund',['correlation_id'=>$correlationId,'optimization_id'=>$o->id]);$this->log->channel('audit')->info('Vendor optimization cancelled',['optimization_id'=>$o->id]);return $o;});}
public function getOptimization(int $optimizationId):VendorOptimization{return VendorOptimization::findOrFail($optimizationId);}
public function getUserOptimizations(int $clientId){return VendorOptimization::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

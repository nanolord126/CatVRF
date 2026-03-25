declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\BusinessValuation\Services;
use App\Domains\BusinessValuation\Models\ValuationExpert;
use App\Domains\BusinessValuation\Models\ValuationProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * BusinessValuationService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BusinessValuationService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $expertId,$valuationType,$valuationHours,$dueDate,string $correlationId=""):ValuationProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("val:proj:".auth()->id(),3))throw new \RuntimeException("Too many",429);RateLimiter::hit("val:proj:".auth()->id(),3600);
return $this->db->transaction(function()use($expertId,$valuationType,$valuationHours,$dueDate,$correlationId){$e=ValuationExpert::findOrFail($expertId);$total=(int)($e->price_kopecks_per_hour*$valuationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'valuation','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=ValuationProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'expert_id'=>$expertId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','valuation_type'=>$valuationType,'valuation_hours'=>$valuationHours,'due_date'=>$dueDate,'tags'=>['valuation'=>true]]);$this->log->channel('audit')->info('Valuation project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):ValuationProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=ValuationProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'val_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Valuation project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):ValuationProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=ValuationProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'val_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Valuation project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):ValuationProject{return ValuationProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return ValuationProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

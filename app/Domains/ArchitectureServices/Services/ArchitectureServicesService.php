declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\ArchitectureServices\Services;
use App\Domains\ArchitectureServices\Models\Architect;
use App\Domains\ArchitectureServices\Models\ArchitectureProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * ArchitectureServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ArchitectureServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $architectId,$projectType,$buildingSqm,$dueDate,string $correlationId=""):ArchitectureProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("arch:project:".auth()->id(),4))throw new \RuntimeException("Too many",429);RateLimiter::hit("arch:project:".auth()->id(),3600);
return $this->db->transaction(function()use($architectId,$projectType,$buildingSqm,$dueDate,$correlationId){$a=Architect::findOrFail($architectId);$total=(int)($a->price_kopecks_per_sqm*$buildingSqm);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'architecture','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=ArchitectureProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'architect_id'=>$architectId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'building_sqm'=>$buildingSqm,'due_date'=>$dueDate,'tags'=>['architecture'=>true]]);$this->log->channel('audit')->info('Architecture project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):ArchitectureProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=ArchitectureProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'arch_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Architecture project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):ArchitectureProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=ArchitectureProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'arch_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Architecture project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):ArchitectureProject{return ArchitectureProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return ArchitectureProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\StrategicPlanning\Services;
use App\Domains\StrategicPlanning\Models\StrategyPlanner;
use App\Domains\StrategicPlanning\Models\StrategyProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * StrategicPlanningService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class StrategicPlanningService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $plannerId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):StrategyProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("strat:proj:".auth()->id(),5))throw new \RuntimeException("Too many",429);RateLimiter::hit("strat:proj:".auth()->id(),3600);
return $this->db->transaction(function()use($plannerId,$projectType,$hoursSpent,$dueDate,$correlationId){$p=StrategyPlanner::findOrFail($plannerId);$total=(int)($p->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'strategy','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$proj=StrategyProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'planner_id'=>$plannerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['strategy'=>true]]);$this->log->channel('audit')->info('Strategy project created',['project_id'=>$proj->id,'correlation_id'=>$correlationId]);return $proj;});
}
public function completeProject(int $projectId,string $correlationId=""):StrategyProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$proj=StrategyProject::findOrFail($projectId);if($proj->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$proj->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$proj->payout_kopecks,'strat_payout',['correlation_id'=>$correlationId,'project_id'=>$proj->id]);$this->log->channel('audit')->info('Strategy project completed',['project_id'=>$proj->id]);return $proj;});}
public function cancelProject(int $projectId,string $correlationId=""):StrategyProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$proj=StrategyProject::findOrFail($projectId);if($proj->status==='completed')throw new \RuntimeException("Cannot cancel",400);$proj->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($proj->payment_status==='completed')$this->wallet->credit(tenant()->id,$proj->total_kopecks,'strat_refund',['correlation_id'=>$correlationId,'project_id'=>$proj->id]);$this->log->channel('audit')->info('Strategy project cancelled',['project_id'=>$proj->id]);return $proj;});}
public function getProject(int $projectId):StrategyProject{return StrategyProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return StrategyProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

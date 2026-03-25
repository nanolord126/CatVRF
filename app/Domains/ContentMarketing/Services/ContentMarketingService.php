declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\ContentMarketing\Services;
use App\Domains\ContentMarketing\Models\ContentStrategist;
use App\Domains\ContentMarketing\Models\StrategyProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * ContentMarketingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ContentMarketingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $strategistId,$projectType,$strategyHours,$dueDate,string $correlationId=""):StrategyProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("content:strat:".auth()->id(),9))throw new \RuntimeException("Too many",429);RateLimiter::hit("content:strat:".auth()->id(),3600);
return $this->db->transaction(function()use($strategistId,$projectType,$strategyHours,$dueDate,$correlationId){$s=ContentStrategist::findOrFail($strategistId);$total=(int)($s->price_kopecks_per_hour*$strategyHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'content_marketing','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=StrategyProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'strategist_id'=>$strategistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'strategy_hours'=>$strategyHours,'due_date'=>$dueDate,'tags'=>['marketing'=>true]]);$this->log->channel('audit')->info('Content strategy project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):StrategyProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=StrategyProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'marketing_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Content strategy project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):StrategyProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=StrategyProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'marketing_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Content strategy project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):StrategyProject{return StrategyProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return StrategyProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

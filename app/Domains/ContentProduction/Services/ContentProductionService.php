declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\ContentProduction\Services;
use App\Domains\ContentProduction\Models\ContentCreator;
use App\Domains\ContentProduction\Models\ContentProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * ContentProductionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ContentProductionService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $creatorId,$contentType,$productionHours,$dueDate,string $correlationId=""):ContentProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("content:proj:".auth()->id(),9))throw new \RuntimeException("Too many",429);RateLimiter::hit("content:proj:".auth()->id(),3600);
return $this->db->transaction(function()use($creatorId,$contentType,$productionHours,$dueDate,$correlationId){$c=ContentCreator::findOrFail($creatorId);$total=(int)($c->price_kopecks_per_hour*$productionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'content_prod','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=ContentProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'creator_id'=>$creatorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','content_type'=>$contentType,'production_hours'=>$productionHours,'due_date'=>$dueDate,'tags'=>['content'=>true]]);$this->log->channel('audit')->info('Content project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):ContentProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=ContentProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'content_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Content project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):ContentProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=ContentProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'content_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Content project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):ContentProject{return ContentProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return ContentProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

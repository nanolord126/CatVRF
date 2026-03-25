declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\VideoProduction\Services;
use App\Domains\VideoProduction\Models\VideoProducer;
use App\Domains\VideoProduction\Models\VideoProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * VideoProductionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class VideoProductionService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $producerId,$projectType,$productionHours,$dueDate,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("video:project:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("video:project:".auth()->id(),3600);
return $this->db->transaction(function()use($producerId,$projectType,$productionHours,$dueDate,$correlationId){$p=VideoProducer::findOrFail($producerId);$total=(int)($p->price_kopecks_per_hour*$productionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'video_project','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$v=VideoProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'producer_id'=>$producerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'production_hours'=>$productionHours,'due_date'=>$dueDate,'tags'=>['video'=>true]]);$this->log->channel('audit')->info('Video project created',['project_id'=>$v->id,'correlation_id'=>$correlationId]);return $v;});
}
public function completeProject(int $projectId,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$v=VideoProject::findOrFail($projectId);if($v->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$v->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$v->payout_kopecks,'video_payout',['correlation_id'=>$correlationId,'project_id'=>$v->id]);$this->log->channel('audit')->info('Video project completed',['project_id'=>$v->id]);return $v;});}
public function cancelProject(int $projectId,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$v=VideoProject::findOrFail($projectId);if($v->status==='completed')throw new \RuntimeException("Cannot cancel",400);$v->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($v->payment_status==='completed')$this->wallet->credit(tenant()->id,$v->total_kopecks,'video_refund',['correlation_id'=>$correlationId,'project_id'=>$v->id]);$this->log->channel('audit')->info('Video project cancelled',['project_id'=>$v->id]);return $v;});}
public function getProject(int $projectId):VideoProject{return VideoProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return VideoProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

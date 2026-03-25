declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\CreativeAgency\Services;
use App\Domains\CreativeAgency\Models\CreativeDirector;
use App\Domains\CreativeAgency\Models\CreativeProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * CreativeAgencyService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreativeAgencyService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $directorId,$projectType,$creativeHours,$dueDate,string $correlationId=""):CreativeProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("creat:proj:".auth()->id(),22))throw new \RuntimeException("Too many",429);RateLimiter::hit("creat:proj:".auth()->id(),3600);
return $this->db->transaction(function()use($directorId,$projectType,$creativeHours,$dueDate,$correlationId){$d=CreativeDirector::findOrFail($directorId);$total=(int)($d->price_kopecks_per_hour*$creativeHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'creative','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=CreativeProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'director_id'=>$directorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'creative_hours'=>$creativeHours,'due_date'=>$dueDate,'tags'=>['creative'=>true]]);$this->log->channel('audit')->info('Creative project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):CreativeProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=CreativeProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'creat_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Creative project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):CreativeProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=CreativeProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'creat_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Creative project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):CreativeProject{return CreativeProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return CreativeProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

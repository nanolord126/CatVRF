declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\UXDesign\Services;
use App\Domains\UXDesign\Models\UXDesigner;
use App\Domains\UXDesign\Models\UXDesignProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * UXDesignService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class UXDesignService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $designerId,$designType,$designHours,$dueDate,string $correlationId=""):UXDesignProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("ux:proj:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("ux:proj:".auth()->id(),3600);
return $this->db->transaction(function()use($designerId,$designType,$designHours,$dueDate,$correlationId){$d=UXDesigner::findOrFail($designerId);$total=(int)($d->price_kopecks_per_hour*$designHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'ux_design','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=UXDesignProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'designer_id'=>$designerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','design_type'=>$designType,'design_hours'=>$designHours,'due_date'=>$dueDate,'tags'=>['ux'=>true]]);$this->log->channel('audit')->info('UX design project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):UXDesignProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=UXDesignProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'ux_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('UX design project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):UXDesignProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=UXDesignProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'ux_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('UX design project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):UXDesignProject{return UXDesignProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return UXDesignProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

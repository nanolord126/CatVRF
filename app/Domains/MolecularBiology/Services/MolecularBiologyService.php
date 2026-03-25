declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\MolecularBiology\Services;
use App\Domains\MolecularBiology\Models\MolecularBiologist;
use App\Domains\MolecularBiology\Models\MolecularProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * MolecularBiologyService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MolecularBiologyService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $biologistId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):MolecularProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("mol:proj:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("mol:proj:".auth()->id(),3600);
return $this->db->transaction(function()use($biologistId,$projectType,$hoursSpent,$dueDate,$correlationId){$b=MolecularBiologist::findOrFail($biologistId);$total=(int)($b->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'mol','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=MolecularProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'biologist_id'=>$biologistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['mol'=>true]]);$this->log->channel('audit')->info('Molecular biology project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):MolecularProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=MolecularProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'mol_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Molecular biology project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):MolecularProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=MolecularProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'mol_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Molecular biology project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):MolecularProject{return MolecularProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return MolecularProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

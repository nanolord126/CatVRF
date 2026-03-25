declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\Copywriting\Services;
use App\Domains\Copywriting\Models\Copywriter;
use App\Domains\Copywriting\Models\CopywritingProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * CopywritingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CopywritingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createProject(int $writerId,$copyType,$wordCount,$dueDate,string $correlationId=""):CopywritingProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("copy:proj:".auth()->id(),13))throw new \RuntimeException("Too many",429);RateLimiter::hit("copy:proj:".auth()->id(),3600);
return $this->db->transaction(function()use($writerId,$copyType,$wordCount,$dueDate,$correlationId){$w=Copywriter::findOrFail($writerId);$total=(int)($w->price_kopecks_per_word*$wordCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'copywriting','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=CopywritingProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'writer_id'=>$writerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','copy_type'=>$copyType,'word_count'=>$wordCount,'due_date'=>$dueDate,'tags'=>['copywriting'=>true]]);$this->log->channel('audit')->info('Copywriting project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):CopywritingProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=CopywritingProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'copy_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Copywriting project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):CopywritingProject{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($projectId,$correlationId){$p=CopywritingProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'copy_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);$this->log->channel('audit')->info('Copywriting project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):CopywritingProject{return CopywritingProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return CopywritingProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

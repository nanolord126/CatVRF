<?php declare(strict_types=1);
namespace App\Domains\ResearchDevelopment\Services;
use App\Domains\ResearchDevelopment\Models\ResearchSpecialist;
use App\Domains\ResearchDevelopment\Models\ResearchProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class ResearchDevelopmentService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $specialistId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):ResearchProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("res:proj:".auth()->id(),13))throw new \RuntimeException("Too many",429);RateLimiter::hit("res:proj:".auth()->id(),3600);
return DB::transaction(function()use($specialistId,$projectType,$hoursSpent,$dueDate,$correlationId){$s=ResearchSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'research','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=ResearchProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['research'=>true]]);Log::channel('audit')->info('Research project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):ResearchProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=ResearchProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'res_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Research project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):ResearchProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=ResearchProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'res_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Research project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):ResearchProject{return ResearchProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return ResearchProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

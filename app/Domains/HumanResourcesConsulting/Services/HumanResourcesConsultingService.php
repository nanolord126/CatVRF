<?php declare(strict_types=1);
namespace App\Domains\HumanResourcesConsulting\Services;
use App\Domains\HumanResourcesConsulting\Models\HumanResourcesConsultant;
use App\Domains\HumanResourcesConsulting\Models\HRProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class HumanResourcesConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $consultantId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):HRProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("hr:proj:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("hr:proj:".auth()->id(),3600);
return DB::transaction(function()use($consultantId,$projectType,$hoursSpent,$dueDate,$correlationId){$c=HumanResourcesConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'hr','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=HRProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['hr'=>true]]);Log::channel('audit')->info('HR project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):HRProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=HRProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'hr_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('HR project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):HRProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=HRProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'hr_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('HR project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):HRProject{return HRProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return HRProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

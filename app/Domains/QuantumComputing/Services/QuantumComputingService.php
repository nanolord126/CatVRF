<?php declare(strict_types=1);
namespace App\Domains\QuantumComputing\Services;
use App\Domains\QuantumComputing\Models\QuantumResearcher;
use App\Domains\QuantumComputing\Models\QuantumProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class QuantumComputingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $researcherId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):QuantumProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("qc:proj:".auth()->id(),23))throw new \RuntimeException("Too many",429);RateLimiter::hit("qc:proj:".auth()->id(),3600);
return DB::transaction(function()use($researcherId,$projectType,$hoursSpent,$dueDate,$correlationId){$r=QuantumResearcher::findOrFail($researcherId);$total=(int)($r->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'qc','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=QuantumProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'researcher_id'=>$researcherId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['qc'=>true]]);Log::channel('audit')->info('Quantum project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):QuantumProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=QuantumProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'qc_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Quantum project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):QuantumProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=QuantumProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'qc_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Quantum project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):QuantumProject{return QuantumProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return QuantumProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

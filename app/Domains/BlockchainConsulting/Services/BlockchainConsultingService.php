<?php declare(strict_types=1);
namespace App\Domains\BlockchainConsulting\Services;
use App\Domains\BlockchainConsulting\Models\BlockchainExpert;
use App\Domains\BlockchainConsulting\Models\BlockchainProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class BlockchainConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $expertId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):BlockchainProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("bc:proj:".auth()->id(),18))throw new \RuntimeException("Too many",429);RateLimiter::hit("bc:proj:".auth()->id(),3600);
return DB::transaction(function()use($expertId,$projectType,$hoursSpent,$dueDate,$correlationId){$e=BlockchainExpert::findOrFail($expertId);$total=(int)($e->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'bc','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=BlockchainProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'expert_id'=>$expertId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['bc'=>true]]);Log::channel('audit')->info('Blockchain project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):BlockchainProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=BlockchainProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'bc_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Blockchain project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):BlockchainProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=BlockchainProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'bc_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Blockchain project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):BlockchainProject{return BlockchainProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return BlockchainProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

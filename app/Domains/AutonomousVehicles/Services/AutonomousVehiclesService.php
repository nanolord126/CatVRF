<?php declare(strict_types=1);
namespace App\Domains\AutonomousVehicles\Services;
use App\Domains\AutonomousVehicles\Models\AVEngineer;
use App\Domains\AutonomousVehicles\Models\AVProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class AutonomousVehiclesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $engineerId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):AVProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("av:proj:".auth()->id(),16))throw new \RuntimeException("Too many",429);RateLimiter::hit("av:proj:".auth()->id(),3600);
return DB::transaction(function()use($engineerId,$projectType,$hoursSpent,$dueDate,$correlationId){$e=AVEngineer::findOrFail($engineerId);$total=(int)($e->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'av','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=AVProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'engineer_id'=>$engineerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['av'=>true]]);Log::channel('audit')->info('AV project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):AVProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=AVProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'av_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('AV project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):AVProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=AVProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'av_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('AV project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):AVProject{return AVProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return AVProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

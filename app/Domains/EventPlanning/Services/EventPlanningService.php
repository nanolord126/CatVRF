<?php declare(strict_types=1);
namespace App\Domains\EventPlanning\Services;
use App\Domains\EventPlanning\Models\EventPlanner;
use App\Domains\EventPlanning\Models\EventProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class EventPlanningService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $plannerId,$eventType,$eventDate,$guestCount,string $correlationId=""):EventProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("event:plan:".auth()->id(),9))throw new \RuntimeException("Too many",429);RateLimiter::hit("event:plan:".auth()->id(),3600);
return DB::transaction(function()use($plannerId,$eventType,$eventDate,$guestCount,$correlationId){$p=EventPlanner::findOrFail($plannerId);$total=$p->price_kopecks_per_event+(1000*$guestCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'event_plan','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=EventProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'planner_id'=>$plannerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.15),'payment_status'=>'pending','event_type'=>$eventType,'event_date'=>$eventDate,'guest_count'=>$guestCount,'tags'=>['event'=>true]]);Log::channel('audit')->info('Event project created',['project_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
}
public function completeProject(int $projectId,string $correlationId=""):EventProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$e=EventProject::findOrFail($projectId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'event_payout',['correlation_id'=>$correlationId,'project_id'=>$e->id]);Log::channel('audit')->info('Event project completed',['project_id'=>$e->id]);return $e;});}
public function cancelProject(int $projectId,string $correlationId=""):EventProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$e=EventProject::findOrFail($projectId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'event_refund',['correlation_id'=>$correlationId,'project_id'=>$e->id]);Log::channel('audit')->info('Event project cancelled',['project_id'=>$e->id]);return $e;});}
public function getProject(int $projectId):EventProject{return EventProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return EventProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

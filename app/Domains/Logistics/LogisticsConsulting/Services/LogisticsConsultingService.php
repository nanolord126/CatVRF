<?php

declare(strict_types=1);

namespace App\Domains\Logistics\LogisticsConsulting\Services;
use App\Domains\Logistics\LogisticsConsulting\Models\LogisticsConsultant;
use App\Domains\Logistics\LogisticsConsulting\Models\LogisticsProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * LogisticsConsultingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class LogisticsConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $consultantId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):LogisticsProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("log:proj:".auth()->id(),9))throw new \RuntimeException("Too many",429);RateLimiter::hit("log:proj:".auth()->id(),3600);
return DB::transaction(function()use($consultantId,$projectType,$hoursSpent,$dueDate,$correlationId){$c=LogisticsConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'logistics','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=LogisticsProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['logistics'=>true]]);Log::channel('audit')->info('Logistics project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):LogisticsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=LogisticsProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'log_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Logistics project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):LogisticsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=LogisticsProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'log_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Logistics project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):LogisticsProject{return LogisticsProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return LogisticsProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

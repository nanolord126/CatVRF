<?php

declare(strict_types=1);

namespace App\Domains\Consulting\OptimizationServices\Services;
use App\Domains\Consulting\OptimizationServices\Models\OptimizationSpecialist;
use App\Domains\Consulting\OptimizationServices\Models\OptimizationProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * OptimizationServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class OptimizationServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $specialistId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):OptimizationProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("opt:proj:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("opt:proj:".auth()->id(),3600);
return DB::transaction(function()use($specialistId,$projectType,$hoursSpent,$dueDate,$correlationId){$s=OptimizationSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'optimization','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=OptimizationProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['optimization'=>true]]);Log::channel('audit')->info('Optimization project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):OptimizationProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=OptimizationProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'opt_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Optimization project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):OptimizationProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=OptimizationProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'opt_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Optimization project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):OptimizationProject{return OptimizationProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return OptimizationProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

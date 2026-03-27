<?php

declare(strict_types=1);

namespace App\Domains\Consulting\InternetServices\Services;
use App\Domains\Consulting\InternetServices\Models\InternetConsultant;
use App\Domains\Consulting\InternetServices\Models\InternetProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * InternetServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class InternetServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $consultantId,$projectType,$consultationHours,$dueDate,string $correlationId=""):InternetProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("inet:proj:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("inet:proj:".auth()->id(),3600);
return DB::transaction(function()use($consultantId,$projectType,$consultationHours,$dueDate,$correlationId){$c=InternetConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'internet','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=InternetProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['internet'=>true]]);Log::channel('audit')->info('Internet project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):InternetProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=InternetProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'inet_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Internet project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):InternetProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=InternetProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'inet_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Internet project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):InternetProject{return InternetProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return InternetProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

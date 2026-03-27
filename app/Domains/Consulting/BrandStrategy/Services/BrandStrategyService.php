<?php

declare(strict_types=1);

namespace App\Domains\Consulting\BrandStrategy\Services;
use App\Domains\Consulting\BrandStrategy\Models\BrandStrategist;
use App\Domains\Consulting\BrandStrategy\Models\BrandProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * BrandStrategyService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BrandStrategyService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $strategistId,$projectType,$consultationHours,$dueDate,string $correlationId=""):BrandProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("brand:proj:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("brand:proj:".auth()->id(),3600);
return DB::transaction(function()use($strategistId,$projectType,$consultationHours,$dueDate,$correlationId){$s=BrandStrategist::findOrFail($strategistId);$total=(int)($s->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'brand_strategy','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=BrandProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'strategist_id'=>$strategistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['brand'=>true]]);Log::channel('audit')->info('Brand project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):BrandProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=BrandProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'brand_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Brand project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):BrandProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=BrandProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'brand_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Brand project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):BrandProject{return BrandProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return BrandProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

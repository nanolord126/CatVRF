<?php

declare(strict_types=1);

namespace App\Domains\Consulting\QualityAssurance\Services;
use App\Domains\Consulting\QualityAssurance\Models\QATester;
use App\Domains\Consulting\QualityAssurance\Models\QAProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * QualityAssuranceService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class QualityAssuranceService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $testerId,$projectType,$testingHours,$dueDate,string $correlationId=""):QAProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("qa:proj:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("qa:proj:".auth()->id(),3600);
return DB::transaction(function()use($testerId,$projectType,$testingHours,$dueDate,$correlationId){$t=QATester::findOrFail($testerId);$total=(int)($t->price_kopecks_per_hour*$testingHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'qa_testing','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=QAProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'tester_id'=>$testerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'testing_hours'=>$testingHours,'due_date'=>$dueDate,'tags'=>['qa'=>true]]);Log::channel('audit')->info('QA project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):QAProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=QAProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'qa_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('QA project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):QAProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=QAProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'qa_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('QA project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):QAProject{return QAProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return QAProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

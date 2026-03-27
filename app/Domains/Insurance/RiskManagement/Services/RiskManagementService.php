<?php

declare(strict_types=1);

namespace App\Domains\Insurance\RiskManagement\Services;
use App\Domains\Insurance\RiskManagement\Models\RiskAnalyst;
use App\Domains\Insurance\RiskManagement\Models\RiskAssessment;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * RiskManagementService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RiskManagementService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createAssessment(int $analystId,$assessmentType,$analysisHours,$dueDate,string $correlationId=""):RiskAssessment{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("risk:assess:".auth()->id(),3))throw new \RuntimeException("Too many",429);RateLimiter::hit("risk:assess:".auth()->id(),3600);
return DB::transaction(function()use($analystId,$assessmentType,$analysisHours,$dueDate,$correlationId){$a=RiskAnalyst::findOrFail($analystId);$total=(int)($a->price_kopecks_per_hour*$analysisHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'risk','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$r=RiskAssessment::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'analyst_id'=>$analystId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','assessment_type'=>$assessmentType,'analysis_hours'=>$analysisHours,'due_date'=>$dueDate,'tags'=>['risk'=>true]]);Log::channel('audit')->info('Risk assessment created',['assessment_id'=>$r->id,'correlation_id'=>$correlationId]);return $r;});
}
public function completeAssessment(int $assessmentId,string $correlationId=""):RiskAssessment{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($assessmentId,$correlationId){$r=RiskAssessment::findOrFail($assessmentId);if($r->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$r->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$r->payout_kopecks,'risk_payout',['correlation_id'=>$correlationId,'assessment_id'=>$r->id]);Log::channel('audit')->info('Risk assessment completed',['assessment_id'=>$r->id]);return $r;});}
public function cancelAssessment(int $assessmentId,string $correlationId=""):RiskAssessment{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($assessmentId,$correlationId){$r=RiskAssessment::findOrFail($assessmentId);if($r->status==='completed')throw new \RuntimeException("Cannot cancel",400);$r->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($r->payment_status==='completed')$this->wallet->credit(tenant()->id,$r->total_kopecks,'risk_refund',['correlation_id'=>$correlationId,'assessment_id'=>$r->id]);Log::channel('audit')->info('Risk assessment cancelled',['assessment_id'=>$r->id]);return $r;});}
public function getAssessment(int $assessmentId):RiskAssessment{return RiskAssessment::findOrFail($assessmentId);}
public function getUserAssessments(int $clientId){return RiskAssessment::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

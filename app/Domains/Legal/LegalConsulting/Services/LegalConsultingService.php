<?php

declare(strict_types=1);

namespace App\Domains\Legal\LegalConsulting\Services;
use App\Domains\Legal\LegalConsulting\Models\LawFirm;
use App\Domains\Legal\LegalConsulting\Models\ConsultationCase;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * LegalConsultingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class LegalConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createCase(int $firmId,$caseType,$consultationHours,$dueDate,string $correlationId=""):ConsultationCase{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("legal:case:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("legal:case:".auth()->id(),3600);
return DB::transaction(function()use($firmId,$caseType,$consultationHours,$dueDate,$correlationId){$f=LawFirm::findOrFail($firmId);$total=(int)($f->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'legal_consult','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=ConsultationCase::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'firm_id'=>$firmId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','case_type'=>$caseType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['legal'=>true]]);Log::channel('audit')->info('Legal consultation case created',['case_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
}
public function completeCase(int $caseId,string $correlationId=""):ConsultationCase{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($caseId,$correlationId){$c=ConsultationCase::findOrFail($caseId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'legal_payout',['correlation_id'=>$correlationId,'case_id'=>$c->id]);Log::channel('audit')->info('Legal consultation case completed',['case_id'=>$c->id]);return $c;});}
public function cancelCase(int $caseId,string $correlationId=""):ConsultationCase{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($caseId,$correlationId){$c=ConsultationCase::findOrFail($caseId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'legal_refund',['correlation_id'=>$correlationId,'case_id'=>$c->id]);Log::channel('audit')->info('Legal consultation case cancelled',['case_id'=>$c->id]);return $c;});}
public function getCase(int $caseId):ConsultationCase{return ConsultationCase::findOrFail($caseId);}
public function getUserCases(int $clientId){return ConsultationCase::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php

declare(strict_types=1);

namespace App\Domains\Legal\TaxConsulting\Services;
use App\Domains\Legal\TaxConsulting\Models\TaxConsultant;
use App\Domains\Legal\TaxConsulting\Models\TaxConsultation;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * TaxConsultingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TaxConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createConsultation(int $consultantId,$consultationType,$consultationHours,$dueDate,string $correlationId=""):TaxConsultation{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("tax:cons:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("tax:cons:".auth()->id(),3600);
return DB::transaction(function()use($consultantId,$consultationType,$consultationHours,$dueDate,$correlationId){$c=TaxConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'tax','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$t=TaxConsultation::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','consultation_type'=>$consultationType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['tax'=>true]]);Log::channel('audit')->info('Tax consultation created',['consultation_id'=>$t->id,'correlation_id'=>$correlationId]);return $t;});
}
public function completeConsultation(int $consultationId,string $correlationId=""):TaxConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$t=TaxConsultation::findOrFail($consultationId);if($t->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$t->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$t->payout_kopecks,'tax_payout',['correlation_id'=>$correlationId,'consultation_id'=>$t->id]);Log::channel('audit')->info('Tax consultation completed',['consultation_id'=>$t->id]);return $t;});}
public function cancelConsultation(int $consultationId,string $correlationId=""):TaxConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$t=TaxConsultation::findOrFail($consultationId);if($t->status==='completed')throw new \RuntimeException("Cannot cancel",400);$t->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($t->payment_status==='completed')$this->wallet->credit(tenant()->id,$t->total_kopecks,'tax_refund',['correlation_id'=>$correlationId,'consultation_id'=>$t->id]);Log::channel('audit')->info('Tax consultation cancelled',['consultation_id'=>$t->id]);return $t;});}
public function getConsultation(int $consultationId):TaxConsultation{return TaxConsultation::findOrFail($consultationId);}
public function getUserConsultations(int $clientId){return TaxConsultation::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

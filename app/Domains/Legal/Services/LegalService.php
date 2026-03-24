<?php declare(strict_types=1);
namespace App\Domains\Legal\Services;
use App\Domains\Legal\Models\Lawyer;
use App\Domains\Legal\Models\LegalConsultation;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class LegalService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createConsultation(int $lawyerId,$consultationDate,$durationHours,$caseType,string $correlationId=""):LegalConsultation{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("legal:consult:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("legal:consult:".auth()->id(),3600);
return DB::transaction(function()use($lawyerId,$consultationDate,$durationHours,$caseType,$correlationId){$l=Lawyer::findOrFail($lawyerId);$total=(int)($l->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'legal_consultation','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=LegalConsultation::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'lawyer_id'=>$lawyerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','consultation_date'=>$consultationDate,'duration_hours'=>$durationHours,'case_type'=>$caseType,'tags'=>['legal'=>true]]);Log::channel('audit')->info('Legal consultation booked',['consultation_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
}
public function completeConsultation(int $consultationId,string $correlationId=""):LegalConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$c=LegalConsultation::findOrFail($consultationId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'legal_payout',['correlation_id'=>$correlationId,'consultation_id'=>$c->id]);Log::channel('audit')->info('Legal consultation completed',['consultation_id'=>$c->id]);return $c;});}
public function cancelConsultation(int $consultationId,string $correlationId=""):LegalConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$c=LegalConsultation::findOrFail($consultationId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'legal_refund',['correlation_id'=>$correlationId,'consultation_id'=>$c->id]);Log::channel('audit')->info('Legal consultation cancelled',['consultation_id'=>$c->id]);return $c;});}
public function getConsultation(int $consultationId):LegalConsultation{return LegalConsultation::findOrFail($consultationId);}
public function getUserConsultations(int $clientId){return LegalConsultation::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

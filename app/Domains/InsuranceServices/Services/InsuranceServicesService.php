<?php declare(strict_types=1);
namespace App\Domains\InsuranceServices\Services;
use App\Domains\InsuranceServices\Models\InsuranceAgent;
use App\Domains\InsuranceServices\Models\InsuranceConsultation;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class InsuranceServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createConsultation(int $agentId,$consultationType,$consultationHours,$dueDate,string $correlationId=""):InsuranceConsultation{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("ins:cons:".auth()->id(),5))throw new \RuntimeException("Too many",429);RateLimiter::hit("ins:cons:".auth()->id(),3600);
return DB::transaction(function()use($agentId,$consultationType,$consultationHours,$dueDate,$correlationId){$a=InsuranceAgent::findOrFail($agentId);$total=(int)($a->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'insurance','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=InsuranceConsultation::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'agent_id'=>$agentId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','consultation_type'=>$consultationType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['insurance'=>true]]);Log::channel('audit')->info('Insurance consultation created',['consultation_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
}
public function completeConsultation(int $consultationId,string $correlationId=""):InsuranceConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$c=InsuranceConsultation::findOrFail($consultationId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'ins_payout',['correlation_id'=>$correlationId,'consultation_id'=>$c->id]);Log::channel('audit')->info('Insurance consultation completed',['consultation_id'=>$c->id]);return $c;});}
public function cancelConsultation(int $consultationId,string $correlationId=""):InsuranceConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$c=InsuranceConsultation::findOrFail($consultationId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'ins_refund',['correlation_id'=>$correlationId,'consultation_id'=>$c->id]);Log::channel('audit')->info('Insurance consultation cancelled',['consultation_id'=>$c->id]);return $c;});}
public function getConsultation(int $consultationId):InsuranceConsultation{return InsuranceConsultation::findOrFail($consultationId);}
public function getUserConsultations(int $clientId){return InsuranceConsultation::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

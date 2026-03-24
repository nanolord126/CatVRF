<?php declare(strict_types=1);
namespace App\Domains\DigitalTransformation\Services;
use App\Domains\DigitalTransformation\Models\TransformationConsultant;
use App\Domains\DigitalTransformation\Models\TransformationInitiative;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class DigitalTransformationService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createInitiative(int $consultantId,$initiativeType,$hoursSpent,$dueDate,string $correlationId=""):TransformationInitiative{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("digit:init:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("digit:init:".auth()->id(),3600);
return DB::transaction(function()use($consultantId,$initiativeType,$hoursSpent,$dueDate,$correlationId){$c=TransformationConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'digit','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$i=TransformationInitiative::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','initiative_type'=>$initiativeType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['digit'=>true]]);Log::channel('audit')->info('Transformation initiative created',['initiative_id'=>$i->id,'correlation_id'=>$correlationId]);return $i;});
}
public function completeInitiative(int $initiativeId,string $correlationId=""):TransformationInitiative{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($initiativeId,$correlationId){$i=TransformationInitiative::findOrFail($initiativeId);if($i->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$i->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$i->payout_kopecks,'digit_payout',['correlation_id'=>$correlationId,'initiative_id'=>$i->id]);Log::channel('audit')->info('Transformation initiative completed',['initiative_id'=>$i->id]);return $i;});}
public function cancelInitiative(int $initiativeId,string $correlationId=""):TransformationInitiative{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($initiativeId,$correlationId){$i=TransformationInitiative::findOrFail($initiativeId);if($i->status==='completed')throw new \RuntimeException("Cannot cancel",400);$i->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($i->payment_status==='completed')$this->wallet->credit(tenant()->id,$i->total_kopecks,'digit_refund',['correlation_id'=>$correlationId,'initiative_id'=>$i->id]);Log::channel('audit')->info('Transformation initiative cancelled',['initiative_id'=>$i->id]);return $i;});}
public function getInitiative(int $initiativeId):TransformationInitiative{return TransformationInitiative::findOrFail($initiativeId);}
public function getUserInitiatives(int $clientId){return TransformationInitiative::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

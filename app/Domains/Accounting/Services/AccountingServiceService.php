<?php declare(strict_types=1);
namespace App\Domains\Accounting\Services;
use App\Domains\Accounting\Models\Accountant;
use App\Domains\Accounting\Models\AccountingService as AccountingServiceModel;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class AccountingServiceService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createRequest(int $accountantId,$serviceType,string $correlationId=""):AccountingServiceModel{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("accounting:service:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("accounting:service:".auth()->id(),3600);
return DB::transaction(function()use($accountantId,$serviceType,$correlationId){$a=Accountant::findOrFail($accountantId);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'accounting_service','correlation_id'=>$correlationId,'amount'=>$a->price_kopecks_per_service]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=AccountingServiceModel::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'accountant_id'=>$accountantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$a->price_kopecks_per_service,'payout_kopecks'=>$a->price_kopecks_per_service-(int)($a->price_kopecks_per_service*0.14),'payment_status'=>'pending','service_type'=>$serviceType,'request_date'=>now(),'tags'=>['accounting'=>true]]);Log::channel('audit')->info('Accounting service requested',['service_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeService(int $serviceId,string $correlationId=""):AccountingServiceModel{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($serviceId,$correlationId){$s=AccountingServiceModel::findOrFail($serviceId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'accounting_payout',['correlation_id'=>$correlationId,'service_id'=>$s->id]);Log::channel('audit')->info('Accounting service completed',['service_id'=>$s->id]);return $s;});}
public function cancelService(int $serviceId,string $correlationId=""):AccountingServiceModel{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($serviceId,$correlationId){$s=AccountingServiceModel::findOrFail($serviceId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'accounting_refund',['correlation_id'=>$correlationId,'service_id'=>$s->id]);Log::channel('audit')->info('Accounting service cancelled',['service_id'=>$s->id]);return $s;});}
public function getService(int $serviceId):AccountingServiceModel{return AccountingServiceModel::findOrFail($serviceId);}
public function getUserServices(int $clientId){return AccountingServiceModel::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

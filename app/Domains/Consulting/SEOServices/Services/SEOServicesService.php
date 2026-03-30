<?php declare(strict_types=1);

namespace App\Domains\Consulting\SEOServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SEOServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createContract(int $specialistId,$contractType,$monthsDuration,$startDate,string $correlationId=""):SEOContract{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("seo:cont:".auth()->id(),5))throw new \RuntimeException("Too many",429);RateLimiter::hit("seo:cont:".auth()->id(),3600);
    return DB::transaction(function()use($specialistId,$contractType,$monthsDuration,$startDate,$correlationId){$s=SEOSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_month*$monthsDuration);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'seo_services','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=SEOContract::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','contract_type'=>$contractType,'months_duration'=>$monthsDuration,'start_date'=>$startDate,'tags'=>['seo'=>true]]);Log::channel('audit')->info('SEO contract created',['contract_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
    }
    public function activateContract(int $contractId,string $correlationId=""):SEOContract{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($contractId,$correlationId){$c=SEOContract::findOrFail($contractId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'seo_payout',['correlation_id'=>$correlationId,'contract_id'=>$c->id]);Log::channel('audit')->info('SEO contract activated',['contract_id'=>$c->id]);return $c;});}
    public function cancelContract(int $contractId,string $correlationId=""):SEOContract{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($contractId,$correlationId){$c=SEOContract::findOrFail($contractId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'seo_refund',['correlation_id'=>$correlationId,'contract_id'=>$c->id]);Log::channel('audit')->info('SEO contract cancelled',['contract_id'=>$c->id]);return $c;});}
    public function getContract(int $contractId):SEOContract{return SEOContract::findOrFail($contractId);}
    public function getUserContracts(int $clientId){return SEOContract::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

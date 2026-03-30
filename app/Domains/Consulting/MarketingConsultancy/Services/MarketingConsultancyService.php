<?php declare(strict_types=1);

namespace App\Domains\Consulting\MarketingConsultancy\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MarketingConsultancyService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createConsultation(int $advisorId,$consultationType,$consultationHours,$dueDate,string $correlationId=""):MarketingConsultation{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("mkt:cons:".auth()->id(),13))throw new \RuntimeException("Too many",429);RateLimiter::hit("mkt:cons:".auth()->id(),3600);
    return DB::transaction(function()use($advisorId,$consultationType,$consultationHours,$dueDate,$correlationId){$a=MarketingAdvisor::findOrFail($advisorId);$total=(int)($a->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'marketing','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=MarketingConsultation::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'advisor_id'=>$advisorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','consultation_type'=>$consultationType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['marketing'=>true]]);Log::channel('audit')->info('Marketing consultation created',['consultation_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
    }
    public function completeConsultation(int $consultationId,string $correlationId=""):MarketingConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$c=MarketingConsultation::findOrFail($consultationId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'mkt_payout',['correlation_id'=>$correlationId,'consultation_id'=>$c->id]);Log::channel('audit')->info('Marketing consultation completed',['consultation_id'=>$c->id]);return $c;});}
    public function cancelConsultation(int $consultationId,string $correlationId=""):MarketingConsultation{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($consultationId,$correlationId){$c=MarketingConsultation::findOrFail($consultationId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'mkt_refund',['correlation_id'=>$correlationId,'consultation_id'=>$c->id]);Log::channel('audit')->info('Marketing consultation cancelled',['consultation_id'=>$c->id]);return $c;});}
    public function getConsultation(int $consultationId):MarketingConsultation{return MarketingConsultation::findOrFail($consultationId);}
    public function getUserConsultations(int $clientId){return MarketingConsultation::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

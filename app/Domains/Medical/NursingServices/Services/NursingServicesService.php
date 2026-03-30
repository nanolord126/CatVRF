<?php declare(strict_types=1);

namespace App\Domains\Medical\NursingServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NursingServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createEngagement(int $agencyId,$careType,$hoursRequired,$startDate,$endDate,string $correlationId=""):NursingEngagement{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("nurs:eng:".auth()->id(),9))throw new \RuntimeException("Too many",429);RateLimiter::hit("nurs:eng:".auth()->id(),3600);
    return DB::transaction(function()use($agencyId,$careType,$hoursRequired,$startDate,$endDate,$correlationId){$a=NursingAgency::findOrFail($agencyId);$total=(int)($a->price_kopecks_per_hour*$hoursRequired);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'nursing','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=NursingEngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'agency_id'=>$agencyId,'patient_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','care_type'=>$careType,'hours_required'=>$hoursRequired,'start_date'=>$startDate,'end_date'=>$endDate,'tags'=>['nursing'=>true]]);Log::channel('audit')->info('Nursing engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
    }
    public function completeEngagement(int $engagementId,string $correlationId=""):NursingEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=NursingEngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'nurs_payout',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('Nursing engagement completed',['engagement_id'=>$e->id]);return $e;});}
    public function cancelEngagement(int $engagementId,string $correlationId=""):NursingEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=NursingEngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'nurs_refund',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('Nursing engagement cancelled',['engagement_id'=>$e->id]);return $e;});}
    public function getEngagement(int $engagementId):NursingEngagement{return NursingEngagement::findOrFail($engagementId);}
    public function getUserEngagements(int $patientId){return NursingEngagement::where('patient_id',$patientId)->orderBy('created_at','desc')->take(10)->get();}
}

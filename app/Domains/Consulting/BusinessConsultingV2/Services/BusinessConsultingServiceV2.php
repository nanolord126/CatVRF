<?php declare(strict_types=1);

namespace App\Domains\Consulting\BusinessConsultingV2\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BusinessConsultingServiceV2 extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createEngagement(int $consultantId,$engagementType,$consultationHours,$dueDate,string $correlationId=""):ConsultingEngagement{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("biz:cons:".auth()->id(),5))throw new \RuntimeException("Too many",429);RateLimiter::hit("biz:cons:".auth()->id(),3600);
    return DB::transaction(function()use($consultantId,$engagementType,$consultationHours,$dueDate,$correlationId){$c=BusinessConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'business_consulting','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=ConsultingEngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','engagement_type'=>$engagementType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['consulting'=>true]]);Log::channel('audit')->info('Consulting engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
    }
    public function completeEngagement(int $engagementId,string $correlationId=""):ConsultingEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=ConsultingEngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'cons_payout',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('Consulting engagement completed',['engagement_id'=>$e->id]);return $e;});}
    public function cancelEngagement(int $engagementId,string $correlationId=""):ConsultingEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=ConsultingEngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'cons_refund',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('Consulting engagement cancelled',['engagement_id'=>$e->id]);return $e;});}
    public function getEngagement(int $engagementId):ConsultingEngagement{return ConsultingEngagement::findOrFail($engagementId);}
    public function getUserEngagements(int $clientId){return ConsultingEngagement::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

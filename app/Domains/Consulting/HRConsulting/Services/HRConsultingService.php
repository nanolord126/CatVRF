<?php declare(strict_types=1);

namespace App\Domains\Consulting\HRConsulting\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HRConsultingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createEngagement(int $specialistId,$serviceType,$consultationHours,$dueDate,string $correlationId=""):HREngagement{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("hr:eng:".auth()->id(),7))throw new \RuntimeException("Too many",429);RateLimiter::hit("hr:eng:".auth()->id(),3600);
    return DB::transaction(function()use($specialistId,$serviceType,$consultationHours,$dueDate,$correlationId){$s=HRSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_hour*$consultationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'hr_consulting','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=HREngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','service_type'=>$serviceType,'consultation_hours'=>$consultationHours,'due_date'=>$dueDate,'tags'=>['hr'=>true]]);Log::channel('audit')->info('HR engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
    }
    public function completeEngagement(int $engagementId,string $correlationId=""):HREngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=HREngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'hr_payout',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('HR engagement completed',['engagement_id'=>$e->id]);return $e;});}
    public function cancelEngagement(int $engagementId,string $correlationId=""):HREngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=HREngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'hr_refund',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('HR engagement cancelled',['engagement_id'=>$e->id]);return $e;});}
    public function getEngagement(int $engagementId):HREngagement{return HREngagement::findOrFail($engagementId);}
    public function getUserEngagements(int $clientId){return HREngagement::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

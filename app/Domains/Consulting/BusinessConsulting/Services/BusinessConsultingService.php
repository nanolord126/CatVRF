<?php declare(strict_types=1);

namespace App\Domains\Consulting\BusinessConsulting\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BusinessConsultingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createSession(int $consultantId,$sessionDate,$durationHours,$topic,string $correlationId=""):ConsultantSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("biz_consult:book:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("biz_consult:book:".auth()->id(),3600);
    return DB::transaction(function()use($consultantId,$sessionDate,$durationHours,$topic,$correlationId){$c=BusinessConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'business_consulting','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=ConsultantSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'topic'=>$topic,'tags'=>['business_consulting'=>true]]);Log::channel('audit')->info('Business consulting session booked',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
    }
    public function completeSession(int $sessionId,string $correlationId=""):ConsultantSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ConsultantSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'biz_consult_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Business consulting session completed',['session_id'=>$s->id]);return $s;});}
    public function cancelSession(int $sessionId,string $correlationId=""):ConsultantSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ConsultantSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'biz_consult_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Business consulting session cancelled',['session_id'=>$s->id]);return $s;});}
    public function getSession(int $sessionId):ConsultantSession{return ConsultantSession->findOrFail($sessionId);}
    public function getUserSessions(int $clientId){return ConsultantSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

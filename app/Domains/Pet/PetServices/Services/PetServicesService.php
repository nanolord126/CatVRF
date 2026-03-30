<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createSession(int $groomerId,$sessionDate,$durationHours,$petType,string $correlationId=""):PetGroomingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("pet:service:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("pet:service:".auth()->id(),3600);
    return DB::transaction(function()use($groomerId,$sessionDate,$durationHours,$petType,$correlationId){$g=PetGroomer::findOrFail($groomerId);$total=(int)($g->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'pet_service','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=PetGroomingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'groomer_id'=>$groomerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'pet_type'=>$petType,'tags'=>['pet_service'=>true]]);Log::channel('audit')->info('Pet service booked',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
    }
    public function completeSession(int $sessionId,string $correlationId=""):PetGroomingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=PetGroomingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'pet_service_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Pet service completed',['session_id'=>$s->id]);return $s;});}
    public function cancelSession(int $sessionId,string $correlationId=""):PetGroomingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=PetGroomingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'pet_service_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Pet service cancelled',['session_id'=>$s->id]);return $s;});}
    public function getSession(int $sessionId):PetGroomingSession{return PetGroomingSession->findOrFail($sessionId);}
    public function getUserSessions(int $clientId){return PetGroomingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

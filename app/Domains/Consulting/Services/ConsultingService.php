<?php

declare(strict_types=1);

namespace App\Domains\Consulting\Services;
use App\Domains\Consulting\Models\Consultant;
use App\Domains\Consulting\Models\ConsultingSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * ConsultingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createSession(int $consultantId,$sessionDate,$durationHours,$topic,string $correlationId=""):ConsultingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("consulting:book:".auth()->id(),25))throw new \RuntimeException("Too many",429);RateLimiter::hit("consulting:book:".auth()->id(),3600);
return DB::transaction(function()use($consultantId,$sessionDate,$durationHours,$topic,$correlationId){$c=Consultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'consulting','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=ConsultingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'topic'=>$topic,'tags'=>['consulting'=>true]]);Log::channel('audit')->info('Consulting session booked',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):ConsultingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ConsultingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'consulting_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Consulting session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):ConsultingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ConsultingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'consulting_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Consulting session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):ConsultingSession{return ConsultingSession->findOrFail($sessionId);}
public function getUserSessions(int $clientId){return ConsultingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\MassageTherapy\Services;
use App\Domains\Beauty\MassageTherapy\Models\MassageTherapist;
use App\Domains\Beauty\MassageTherapy\Models\MassageSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * MassageTherapyService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MassageTherapyService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createSession(int $therapistId,$massageType,$durationMinutes,$sessionDate,string $correlationId=""):MassageSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("massage:sess:".auth()->id(),24))throw new \RuntimeException("Too many",429);RateLimiter::hit("massage:sess:".auth()->id(),3600);
return DB::transaction(function()use($therapistId,$massageType,$durationMinutes,$sessionDate,$correlationId){$t=MassageTherapist::findOrFail($therapistId);$total=(int)($t->price_kopecks_per_minute*$durationMinutes);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'massage_therapy','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=MassageSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'therapist_id'=>$therapistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','massage_type'=>$massageType,'duration_minutes'=>$durationMinutes,'session_date'=>$sessionDate,'tags'=>['massage'=>true]]);Log::channel('audit')->info('Massage session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):MassageSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=MassageSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'massage_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Massage session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):MassageSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=MassageSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'massage_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Massage session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):MassageSession{return MassageSession->findOrFail($sessionId);}
public function getUserSessions(int $clientId){return MassageSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

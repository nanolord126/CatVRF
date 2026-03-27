<?php

declare(strict_types=1);

namespace App\Domains\Photography\PhotographyServices\Services;
use App\Domains\Photography\PhotographyServices\Models\Photographer;
use App\Domains\Photography\PhotographyServices\Models\PhotoSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * PhotographyServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PhotographyServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createSession(int $photographerId,$sessionDate,$durationHours,$eventType,string $correlationId=""):PhotoSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("photo:session:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("photo:session:".auth()->id(),3600);
return DB::transaction(function()use($photographerId,$sessionDate,$durationHours,$eventType,$correlationId){$p=Photographer::findOrFail($photographerId);$total=(int)($p->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'photo_session','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=PhotoSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'photographer_id'=>$photographerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'event_type'=>$eventType,'tags'=>['photo'=>true]]);Log::channel('audit')->info('Photo session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):PhotoSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=PhotoSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'photo_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Photo session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):PhotoSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=PhotoSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'photo_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Photo session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):PhotoSession{return PhotoSession->findOrFail($sessionId);}
public function getUserSessions(int $clientId){return PhotoSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

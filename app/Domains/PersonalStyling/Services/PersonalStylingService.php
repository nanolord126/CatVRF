<?php declare(strict_types=1);
namespace App\Domains\PersonalStyling\Services;
use App\Domains\PersonalStyling\Models\PersonalStylist;
use App\Domains\PersonalStyling\Models\StylingSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class PersonalStylingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createSession(int $stylistId,$styleType,$sessionHours,$sessionDate,string $correlationId=""):StylingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("style:sess:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("style:sess:".auth()->id(),3600);
return DB::transaction(function()use($stylistId,$styleType,$sessionHours,$sessionDate,$correlationId){$s=PersonalStylist::findOrFail($stylistId);$total=(int)($s->price_kopecks_per_hour*$sessionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'personal_styling','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$ss=StylingSession::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'stylist_id'=>$stylistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','style_type'=>$styleType,'session_hours'=>$sessionHours,'session_date'=>$sessionDate,'tags'=>['styling'=>true]]);Log::channel('audit')->info('Styling session created',['session_id'=>$ss->id,'correlation_id'=>$correlationId]);return $ss;});
}
public function completeSession(int $sessionId,string $correlationId=""):StylingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$ss=StylingSession::findOrFail($sessionId);if($ss->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$ss->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$ss->payout_kopecks,'style_payout',['correlation_id'=>$correlationId,'session_id'=>$ss->id]);Log::channel('audit')->info('Styling session completed',['session_id'=>$ss->id]);return $ss;});}
public function cancelSession(int $sessionId,string $correlationId=""):StylingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$ss=StylingSession::findOrFail($sessionId);if($ss->status==='completed')throw new \RuntimeException("Cannot cancel",400);$ss->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($ss->payment_status==='completed')$this->wallet->credit(tenant()->id,$ss->total_kopecks,'style_refund',['correlation_id'=>$correlationId,'session_id'=>$ss->id]);Log::channel('audit')->info('Styling session cancelled',['session_id'=>$ss->id]);return $ss;});}
public function getSession(int $sessionId):StylingSession{return StylingSession::findOrFail($sessionId);}
public function getUserSessions(int $clientId){return StylingSession::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);
namespace App\Domains\Babysitting\Services;
use App\Domains\Babysitting\Models\Babysitter;
use App\Domains\Babysitting\Models\BabysittingSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class BabysittingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createSession(int $sitterId,$sessionDate,$durationHours,$kidsAges,string $correlationId=""):BabysittingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("babysitting:book:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("babysitting:book:".auth()->id(),3600);
return DB::transaction(function()use($sitterId,$sessionDate,$durationHours,$kidsAges,$correlationId){$s=Babysitter::findOrFail($sitterId);$total=(int)($s->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'babysitting','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=BabysittingSession::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'sitter_id'=>$sitterId,'parent_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'kids_ages'=>$kidsAges,'tags'=>['babysitting'=>true]]);Log::channel('audit')->info('Babysitting session booked',['session_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeSession(int $sessionId,string $correlationId=""):BabysittingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$b=BabysittingSession::findOrFail($sessionId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'babysitting_payout',['correlation_id'=>$correlationId,'session_id'=>$b->id]);Log::channel('audit')->info('Babysitting session completed',['session_id'=>$b->id]);return $b;});}
public function cancelSession(int $sessionId,string $correlationId=""):BabysittingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$b=BabysittingSession::findOrFail($sessionId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'babysitting_refund',['correlation_id'=>$correlationId,'session_id'=>$b->id]);Log::channel('audit')->info('Babysitting session cancelled',['session_id'=>$b->id]);return $b;});}
public function getSession(int $sessionId):BabysittingSession{return BabysittingSession::findOrFail($sessionId);}
public function getUserSessions(int $parentId){return BabysittingSession::where('parent_id',$parentId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);
namespace App\Domains\BoardGames\Services;
use App\Domains\BoardGames\Models\BoardGameCafe;
use App\Domains\BoardGames\Models\GameSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class BoardGamesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createSession(int $cafeId,$sessionDate,$durationHours,$tableNumber,string $correlationId=""):GameSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("boardgame:session:".auth()->id(),25))throw new \RuntimeException("Too many",429);RateLimiter::hit("boardgame:session:".auth()->id(),3600);
return DB::transaction(function()use($cafeId,$sessionDate,$durationHours,$tableNumber,$correlationId){$c=BoardGameCafe::findOrFail($cafeId);$total=(int)($c->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'boardgame_session','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=GameSession::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'cafe_id'=>$cafeId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'table_number'=>$tableNumber,'tags'=>['boardgame'=>true]]);Log::channel('audit')->info('Board game session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):GameSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=GameSession::findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'boardgame_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Board game session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):GameSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=GameSession::findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'boardgame_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Board game session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):GameSession{return GameSession::findOrFail($sessionId);}
public function getUserSessions(int $clientId){return GameSession::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

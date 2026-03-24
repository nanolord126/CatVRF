<?php declare(strict_types=1);
namespace App\Domains\CareerCounseling\Services;
use App\Domains\CareerCounseling\Models\CareerCounselor;
use App\Domains\CareerCounseling\Models\CounselingSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class CareerCounselingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createSession(int $counselorId,$sessionType,$sessionHours,$dueDate,string $correlationId=""):CounselingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("career:sess:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("career:sess:".auth()->id(),3600);
return DB::transaction(function()use($counselorId,$sessionType,$sessionHours,$dueDate,$correlationId){$c=CareerCounselor::findOrFail($counselorId);$total=(int)($c->price_kopecks_per_hour*$sessionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'career','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=CounselingSession::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'counselor_id'=>$counselorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_type'=>$sessionType,'session_hours'=>$sessionHours,'due_date'=>$dueDate,'tags'=>['career'=>true]]);Log::channel('audit')->info('Counseling session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):CounselingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=CounselingSession::findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'career_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Counseling session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):CounselingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=CounselingSession::findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'career_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Counseling session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):CounselingSession{return CounselingSession::findOrFail($sessionId);}
public function getUserSessions(int $clientId){return CounselingSession::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

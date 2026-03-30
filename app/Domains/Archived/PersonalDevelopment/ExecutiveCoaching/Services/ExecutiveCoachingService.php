<?php declare(strict_types=1);

namespace App\Domains\Archived\PersonalDevelopment\ExecutiveCoaching\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ExecutiveCoachingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}


    public function createSession(int $coachId,$focusArea,$sessionHours,$dueDate,string $correlationId=""):ExecutiveSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("exec:sess:".auth()->id(),4))throw new \RuntimeException("Too many",429);RateLimiter::hit("exec:sess:".auth()->id(),3600);


    return DB::transaction(function()use($coachId,$focusArea,$sessionHours,$dueDate,$correlationId){$c=ExecutiveCoach::findOrFail($coachId);$total=(int)($c->price_kopecks_per_hour*$sessionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'exec_coaching','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=ExecutiveSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'coach_id'=>$coachId,'executive_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','focus_area'=>$focusArea,'session_hours'=>$sessionHours,'due_date'=>$dueDate,'tags'=>['exec'=>true]]);Log::channel('audit')->info('Executive session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});


    }


    public function completeSession(int $sessionId,string $correlationId=""):ExecutiveSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ExecutiveSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'exec_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Executive session completed',['session_id'=>$s->id]);return $s;});}


    public function cancelSession(int $sessionId,string $correlationId=""):ExecutiveSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ExecutiveSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'exec_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Executive session cancelled',['session_id'=>$s->id]);return $s;});}


    public function getSession(int $sessionId):ExecutiveSession{return ExecutiveSession->findOrFail($sessionId);}


    public function getUserSessions(int $executiveId){return ExecutiveSession->where('executive_id',$executiveId)->orderBy('created_at','desc')->take(10)->get();}
}

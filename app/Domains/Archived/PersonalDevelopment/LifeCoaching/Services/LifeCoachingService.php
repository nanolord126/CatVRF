<?php declare(strict_types=1);

namespace App\Domains\Archived\PersonalDevelopment\LifeCoaching\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LifeCoachingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}


    public function createSession(int $coachId,$focusArea,$sessionHours,$sessionDate,string $correlationId=""):CoachingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("coach:sess:".auth()->id(),7))throw new \RuntimeException("Too many",429);RateLimiter::hit("coach:sess:".auth()->id(),3600);


    return DB::transaction(function()use($coachId,$focusArea,$sessionHours,$sessionDate,$correlationId){$c=LifeCoach::findOrFail($coachId);$total=(int)($c->price_kopecks_per_hour*$sessionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'life_coaching','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=CoachingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'coach_id'=>$coachId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','focus_area'=>$focusArea,'session_hours'=>$sessionHours,'session_date'=>$sessionDate,'tags'=>['coaching'=>true]]);Log::channel('audit')->info('Coaching session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});


    }


    public function completeSession(int $sessionId,string $correlationId=""):CoachingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=CoachingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'coach_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Coaching session completed',['session_id'=>$s->id]);return $s;});}


    public function cancelSession(int $sessionId,string $correlationId=""):CoachingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=CoachingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'coach_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Coaching session cancelled',['session_id'=>$s->id]);return $s;});}


    public function getSession(int $sessionId):CoachingSession{return CoachingSession->findOrFail($sessionId);}


    public function getUserSessions(int $clientId){return CoachingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php

declare(strict_types=1);

namespace App\Domains\Sports\PersonalTraining\Services;
use App\Domains\Sports\PersonalTraining\Models\PersonalTrainer;
use App\Domains\Sports\PersonalTraining\Models\TrainingSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * PersonalTrainingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PersonalTrainingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createSession(int $trainerId,$workoutType,$sessionHours,$sessionDate,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("train:sess:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("train:sess:".auth()->id(),3600);
return DB::transaction(function()use($trainerId,$workoutType,$sessionHours,$sessionDate,$correlationId){$t=PersonalTrainer::findOrFail($trainerId);$total=(int)($t->price_kopecks_per_hour*$sessionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'personal_training','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=TrainingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'trainer_id'=>$trainerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','workout_type'=>$workoutType,'session_hours'=>$sessionHours,'session_date'=>$sessionDate,'tags'=>['training'=>true]]);Log::channel('audit')->info('Training session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TrainingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'train_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Training session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TrainingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'train_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Training session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):TrainingSession{return TrainingSession->findOrFail($sessionId);}
public function getUserSessions(int $clientId){return TrainingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

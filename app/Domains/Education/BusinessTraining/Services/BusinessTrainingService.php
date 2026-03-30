<?php declare(strict_types=1);

namespace App\Domains\Education\BusinessTraining\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BusinessTrainingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createSession(int $providerId,$trainingType,$trainingHours,$dueDate,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("train:sess:".auth()->id(),14))throw new \RuntimeException("Too many",429);RateLimiter::hit("train:sess:".auth()->id(),3600);
    return DB::transaction(function()use($providerId,$trainingType,$trainingHours,$dueDate,$correlationId){$p=TrainingProvider::findOrFail($providerId);$total=(int)($p->price_kopecks_per_hour*$trainingHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'training','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=TrainingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'provider_id'=>$providerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','training_type'=>$trainingType,'training_hours'=>$trainingHours,'due_date'=>$dueDate,'tags'=>['training'=>true]]);Log::channel('audit')->info('Training session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
    }
    public function completeSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TrainingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'train_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Training session completed',['session_id'=>$s->id]);return $s;});}
    public function cancelSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=TrainingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'train_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Training session cancelled',['session_id'=>$s->id]);return $s;});}
    public function getSession(int $sessionId):TrainingSession{return TrainingSession->findOrFail($sessionId);}
    public function getUserSessions(int $clientId){return TrainingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

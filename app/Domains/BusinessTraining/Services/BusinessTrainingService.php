declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\BusinessTraining\Services;
use App\Domains\BusinessTraining\Models\TrainingProvider;
use App\Domains\BusinessTraining\Models\TrainingSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * BusinessTrainingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BusinessTrainingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createSession(int $providerId,$trainingType,$trainingHours,$dueDate,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("train:sess:".auth()->id(),14))throw new \RuntimeException("Too many",429);RateLimiter::hit("train:sess:".auth()->id(),3600);
return $this->db->transaction(function()use($providerId,$trainingType,$trainingHours,$dueDate,$correlationId){$p=TrainingProvider::findOrFail($providerId);$total=(int)($p->price_kopecks_per_hour*$trainingHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'training','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=Training$this->session->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'provider_id'=>$providerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','training_type'=>$trainingType,'training_hours'=>$trainingHours,'due_date'=>$dueDate,'tags'=>['training'=>true]]);$this->log->channel('audit')->info('Training session created',['session_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=Training$this->session->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'train_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);$this->log->channel('audit')->info('Training session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):TrainingSession{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($sessionId,$correlationId){$s=Training$this->session->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'train_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);$this->log->channel('audit')->info('Training session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):TrainingSession{return Training$this->session->findOrFail($sessionId);}
public function getUserSessions(int $clientId){return Training$this->session->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

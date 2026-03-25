declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\SuccessionPlanning\Services;
use App\Domains\SuccessionPlanning\Models\SuccessionPlanner;
use App\Domains\SuccessionPlanning\Models\SuccessionPlan;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * SuccessionPlanningService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SuccessionPlanningService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createPlan(int $plannerId,$planType,$hoursSpent,$dueDate,string $correlationId=""):SuccessionPlan{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("succ:plan:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("succ:plan:".auth()->id(),3600);
return $this->db->transaction(function()use($plannerId,$planType,$hoursSpent,$dueDate,$correlationId){$p=SuccessionPlanner::findOrFail($plannerId);$total=(int)($p->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'succ','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$sp=SuccessionPlan::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'planner_id'=>$plannerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','plan_type'=>$planType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['succ'=>true]]);$this->log->channel('audit')->info('Succession plan created',['plan_id'=>$sp->id,'correlation_id'=>$correlationId]);return $sp;});
}
public function completePlan(int $planId,string $correlationId=""):SuccessionPlan{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($planId,$correlationId){$sp=SuccessionPlan::findOrFail($planId);if($sp->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$sp->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$sp->payout_kopecks,'succ_payout',['correlation_id'=>$correlationId,'plan_id'=>$sp->id]);$this->log->channel('audit')->info('Succession plan completed',['plan_id'=>$sp->id]);return $sp;});}
public function cancelPlan(int $planId,string $correlationId=""):SuccessionPlan{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($planId,$correlationId){$sp=SuccessionPlan::findOrFail($planId);if($sp->status==='completed')throw new \RuntimeException("Cannot cancel",400);$sp->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($sp->payment_status==='completed')$this->wallet->credit(tenant()->id,$sp->total_kopecks,'succ_refund',['correlation_id'=>$correlationId,'plan_id'=>$sp->id]);$this->log->channel('audit')->info('Succession plan cancelled',['plan_id'=>$sp->id]);return $sp;});}
public function getPlan(int $planId):SuccessionPlan{return SuccessionPlan::findOrFail($planId);}
public function getUserPlans(int $clientId){return SuccessionPlan::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

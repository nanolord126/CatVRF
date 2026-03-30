<?php declare(strict_types=1);

namespace App\Domains\Consulting\FinancialPlanning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FinancialPlanningService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createPlan(int $advisorId,$planType,$planningHours,$dueDate,string $correlationId=""):FinancialPlan{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("fin:plan:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("fin:plan:".auth()->id(),3600);
    return DB::transaction(function()use($advisorId,$planType,$planningHours,$dueDate,$correlationId){$a=FinancialAdvisor::findOrFail($advisorId);$total=(int)($a->price_kopecks_per_hour*$planningHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'financial_plan','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=FinancialPlan::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'advisor_id'=>$advisorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','plan_type'=>$planType,'planning_hours'=>$planningHours,'due_date'=>$dueDate,'tags'=>['financial'=>true]]);Log::channel('audit')->info('Financial plan created',['plan_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completePlan(int $planId,string $correlationId=""):FinancialPlan{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($planId,$correlationId){$p=FinancialPlan::findOrFail($planId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'fin_payout',['correlation_id'=>$correlationId,'plan_id'=>$p->id]);Log::channel('audit')->info('Financial plan completed',['plan_id'=>$p->id]);return $p;});}
    public function cancelPlan(int $planId,string $correlationId=""):FinancialPlan{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($planId,$correlationId){$p=FinancialPlan::findOrFail($planId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'fin_refund',['correlation_id'=>$correlationId,'plan_id'=>$p->id]);Log::channel('audit')->info('Financial plan cancelled',['plan_id'=>$p->id]);return $p;});}
    public function getPlan(int $planId):FinancialPlan{return FinancialPlan::findOrFail($planId);}
    public function getUserPlans(int $clientId){return FinancialPlan::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

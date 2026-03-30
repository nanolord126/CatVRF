<?php declare(strict_types=1);

namespace App\Domains\Legal\ConflictResolution\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConflictResolutionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createCase(int $specialistId,$caseType,$hoursSpent,$dueDate,string $correlationId=""):MediationCase{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("conflict:case:".auth()->id(),7))throw new \RuntimeException("Too many",429);RateLimiter::hit("conflict:case:".auth()->id(),3600);
    return DB::transaction(function()use($specialistId,$caseType,$hoursSpent,$dueDate,$correlationId){$s=MediationSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'conflict','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=MediationCase::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','case_type'=>$caseType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['conflict'=>true]]);Log::channel('audit')->info('Mediation case created',['case_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
    }
    public function completeCase(int $caseId,string $correlationId=""):MediationCase{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($caseId,$correlationId){$c=MediationCase::findOrFail($caseId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'conflict_payout',['correlation_id'=>$correlationId,'case_id'=>$c->id]);Log::channel('audit')->info('Mediation case completed',['case_id'=>$c->id]);return $c;});}
    public function cancelCase(int $caseId,string $correlationId=""):MediationCase{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($caseId,$correlationId){$c=MediationCase::findOrFail($caseId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'conflict_refund',['correlation_id'=>$correlationId,'case_id'=>$c->id]);Log::channel('audit')->info('Mediation case cancelled',['case_id'=>$c->id]);return $c;});}
    public function getCase(int $caseId):MediationCase{return MediationCase::findOrFail($caseId);}
    public function getUserCases(int $clientId){return MediationCase::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

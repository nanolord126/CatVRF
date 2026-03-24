<?php declare(strict_types=1);
namespace App\Domains\AssuranceServices\Services;
use App\Domains\AssuranceServices\Models\AssuranceAuditor;
use App\Domains\AssuranceServices\Models\QualityAudit;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class AssuranceServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createAudit(int $auditorId,$auditType,$hoursSpent,$dueDate,string $correlationId=""):QualityAudit{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("assur:audit:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("assur:audit:".auth()->id(),3600);
return DB::transaction(function()use($auditorId,$auditType,$hoursSpent,$dueDate,$correlationId){$a=AssuranceAuditor::findOrFail($auditorId);$total=(int)($a->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'assurance','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$q=QualityAudit::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'auditor_id'=>$auditorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','audit_type'=>$auditType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['assurance'=>true]]);Log::channel('audit')->info('Quality audit created',['audit_id'=>$q->id,'correlation_id'=>$correlationId]);return $q;});
}
public function completeAudit(int $auditId,string $correlationId=""):QualityAudit{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($auditId,$correlationId){$q=QualityAudit::findOrFail($auditId);if($q->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$q->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$q->payout_kopecks,'assur_payout',['correlation_id'=>$correlationId,'audit_id'=>$q->id]);Log::channel('audit')->info('Quality audit completed',['audit_id'=>$q->id]);return $q;});}
public function cancelAudit(int $auditId,string $correlationId=""):QualityAudit{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($auditId,$correlationId){$q=QualityAudit::findOrFail($auditId);if($q->status==='completed')throw new \RuntimeException("Cannot cancel",400);$q->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($q->payment_status==='completed')$this->wallet->credit(tenant()->id,$q->total_kopecks,'assur_refund',['correlation_id'=>$correlationId,'audit_id'=>$q->id]);Log::channel('audit')->info('Quality audit cancelled',['audit_id'=>$q->id]);return $q;});}
public function getAudit(int $auditId):QualityAudit{return QualityAudit::findOrFail($auditId);}
public function getUserAudits(int $clientId){return QualityAudit::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);
namespace App\Domains\DataPrivacy\Services;
use App\Domains\DataPrivacy\Models\PrivacyConsultant;
use App\Domains\DataPrivacy\Models\PrivacyAudit;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class DataPrivacyService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createAudit(int $consultantId,$auditType,$hoursSpent,$dueDate,string $correlationId=""):PrivacyAudit{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("privacy:audit:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("privacy:audit:".auth()->id(),3600);
return DB::transaction(function()use($consultantId,$auditType,$hoursSpent,$dueDate,$correlationId){$c=PrivacyConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'privacy','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$a=PrivacyAudit::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','audit_type'=>$auditType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['privacy'=>true]]);Log::channel('audit')->info('Privacy audit created',['audit_id'=>$a->id,'correlation_id'=>$correlationId]);return $a;});
}
public function completeAudit(int $auditId,string $correlationId=""):PrivacyAudit{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($auditId,$correlationId){$a=PrivacyAudit::findOrFail($auditId);if($a->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$a->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$a->payout_kopecks,'privacy_payout',['correlation_id'=>$correlationId,'audit_id'=>$a->id]);Log::channel('audit')->info('Privacy audit completed',['audit_id'=>$a->id]);return $a;});}
public function cancelAudit(int $auditId,string $correlationId=""):PrivacyAudit{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($auditId,$correlationId){$a=PrivacyAudit::findOrFail($auditId);if($a->status==='completed')throw new \RuntimeException("Cannot cancel",400);$a->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($a->payment_status==='completed')$this->wallet->credit(tenant()->id,$a->total_kopecks,'privacy_refund',['correlation_id'=>$correlationId,'audit_id'=>$a->id]);Log::channel('audit')->info('Privacy audit cancelled',['audit_id'=>$a->id]);return $a;});}
public function getAudit(int $auditId):PrivacyAudit{return PrivacyAudit::findOrFail($auditId);}
public function getUserAudits(int $clientId){return PrivacyAudit::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

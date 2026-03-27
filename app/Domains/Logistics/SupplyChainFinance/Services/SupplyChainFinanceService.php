<?php

declare(strict_types=1);

namespace App\Domains\Logistics\SupplyChainFinance\Services;
use App\Domains\Logistics\SupplyChainFinance\Models\SCFAdvisor;
use App\Domains\Logistics\SupplyChainFinance\Models\SCFEngagement;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * SupplyChainFinanceService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SupplyChainFinanceService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createEngagement(int $advisorId,$engagementType,$hoursSpent,$dueDate,string $correlationId=""):SCFEngagement{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("scf:eng:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("scf:eng:".auth()->id(),3600);
return DB::transaction(function()use($advisorId,$engagementType,$hoursSpent,$dueDate,$correlationId){$a=SCFAdvisor::findOrFail($advisorId);$total=(int)($a->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'scf','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=SCFEngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'advisor_id'=>$advisorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','engagement_type'=>$engagementType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['scf'=>true]]);Log::channel('audit')->info('SCF engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
}
public function completeEngagement(int $engagementId,string $correlationId=""):SCFEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=SCFEngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'scf_payout',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('SCF engagement completed',['engagement_id'=>$e->id]);return $e;});}
public function cancelEngagement(int $engagementId,string $correlationId=""):SCFEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=SCFEngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'scf_refund',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('SCF engagement cancelled',['engagement_id'=>$e->id]);return $e;});}
public function getEngagement(int $engagementId):SCFEngagement{return SCFEngagement::findOrFail($engagementId);}
public function getUserEngagements(int $clientId){return SCFEngagement::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

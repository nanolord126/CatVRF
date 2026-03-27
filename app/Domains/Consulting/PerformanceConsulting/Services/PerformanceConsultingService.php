<?php

declare(strict_types=1);

namespace App\Domains\Consulting\PerformanceConsulting\Services;
use App\Domains\Consulting\PerformanceConsulting\Models\PerformanceCoach;
use App\Domains\Consulting\PerformanceConsulting\Models\PerformanceEngagement;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * PerformanceConsultingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PerformanceConsultingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createEngagement(int $coachId,$engagementType,$hoursSpent,$dueDate,string $correlationId=""):PerformanceEngagement{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("perf:eng:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("perf:eng:".auth()->id(),3600);
return DB::transaction(function()use($coachId,$engagementType,$hoursSpent,$dueDate,$correlationId){$c=PerformanceCoach::findOrFail($coachId);$total=(int)($c->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'perf','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=PerformanceEngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'coach_id'=>$coachId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','engagement_type'=>$engagementType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['perf'=>true]]);Log::channel('audit')->info('Performance engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
}
public function completeEngagement(int $engagementId,string $correlationId=""):PerformanceEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=PerformanceEngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'perf_payout',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('Performance engagement completed',['engagement_id'=>$e->id]);return $e;});}
public function cancelEngagement(int $engagementId,string $correlationId=""):PerformanceEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=PerformanceEngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'perf_refund',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('Performance engagement cancelled',['engagement_id'=>$e->id]);return $e;});}
public function getEngagement(int $engagementId):PerformanceEngagement{return PerformanceEngagement::findOrFail($engagementId);}
public function getUserEngagements(int $clientId){return PerformanceEngagement::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php

declare(strict_types=1);

namespace App\Domains\Consulting\CompetitiveIntelligence\Services;
use App\Domains\Consulting\CompetitiveIntelligence\Models\IntelligenceAnalyst;
use App\Domains\Consulting\CompetitiveIntelligence\Models\IntelligenceReport;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * CompetitiveIntelligenceService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CompetitiveIntelligenceService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createReport(int $analystId,$reportType,$analysisHours,$dueDate,string $correlationId=""):IntelligenceReport{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("intel:rep:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("intel:rep:".auth()->id(),3600);
return DB::transaction(function()use($analystId,$reportType,$analysisHours,$dueDate,$correlationId){$a=IntelligenceAnalyst::findOrFail($analystId);$total=(int)($a->price_kopecks_per_hour*$analysisHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'intel','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$r=IntelligenceReport::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'analyst_id'=>$analystId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','report_type'=>$reportType,'analysis_hours'=>$analysisHours,'due_date'=>$dueDate,'tags'=>['intel'=>true]]);Log::channel('audit')->info('Intelligence report created',['report_id'=>$r->id,'correlation_id'=>$correlationId]);return $r;});
}
public function completeReport(int $reportId,string $correlationId=""):IntelligenceReport{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($reportId,$correlationId){$r=IntelligenceReport::findOrFail($reportId);if($r->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$r->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$r->payout_kopecks,'intel_payout',['correlation_id'=>$correlationId,'report_id'=>$r->id]);Log::channel('audit')->info('Intelligence report completed',['report_id'=>$r->id]);return $r;});}
public function cancelReport(int $reportId,string $correlationId=""):IntelligenceReport{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($reportId,$correlationId){$r=IntelligenceReport::findOrFail($reportId);if($r->status==='completed')throw new \RuntimeException("Cannot cancel",400);$r->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($r->payment_status==='completed')$this->wallet->credit(tenant()->id,$r->total_kopecks,'intel_refund',['correlation_id'=>$correlationId,'report_id'=>$r->id]);Log::channel('audit')->info('Intelligence report cancelled',['report_id'=>$r->id]);return $r;});}
public function getReport(int $reportId):IntelligenceReport{return IntelligenceReport::findOrFail($reportId);}
public function getUserReports(int $clientId){return IntelligenceReport::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

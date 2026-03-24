<?php declare(strict_types=1);
namespace App\Domains\TradeServices\Services;
use App\Domains\TradeServices\Models\Tradesperson;
use App\Domains\TradeServices\Models\TradeJob;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class TradeServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createJob(int $tradepersonId,$jobDate,$durationHours,$jobType,string $correlationId=""):TradeJob{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("trade:job:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("trade:job:".auth()->id(),3600);
return DB::transaction(function()use($tradepersonId,$jobDate,$durationHours,$jobType,$correlationId){$t=Tradesperson::findOrFail($tradepersonId);$total=(int)($t->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'trade_job','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$j=TradeJob::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'tradesperson_id'=>$tradepersonId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','job_date'=>$jobDate,'duration_hours'=>$durationHours,'job_type'=>$jobType,'tags'=>['trade'=>true]]);Log::channel('audit')->info('Trade job created',['job_id'=>$j->id,'correlation_id'=>$correlationId]);return $j;});
}
public function completeJob(int $jobId,string $correlationId=""):TradeJob{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($jobId,$correlationId){$j=TradeJob::findOrFail($jobId);if($j->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$j->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$j->payout_kopecks,'trade_payout',['correlation_id'=>$correlationId,'job_id'=>$j->id]);Log::channel('audit')->info('Trade job completed',['job_id'=>$j->id]);return $j;});}
public function cancelJob(int $jobId,string $correlationId=""):TradeJob{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($jobId,$correlationId){$j=TradeJob::findOrFail($jobId);if($j->status==='completed')throw new \RuntimeException("Cannot cancel",400);$j->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($j->payment_status==='completed')$this->wallet->credit(tenant()->id,$j->total_kopecks,'trade_refund',['correlation_id'=>$correlationId,'job_id'=>$j->id]);Log::channel('audit')->info('Trade job cancelled',['job_id'=>$j->id]);return $j;});}
public function getJob(int $jobId):TradeJob{return TradeJob::findOrFail($jobId);}
public function getUserJobs(int $clientId){return TradeJob::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

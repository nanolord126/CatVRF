<?php declare(strict_types=1);

namespace App\Domains\Gardening\GardenServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GardenServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createJob(int $professionalId,$jobDate,$durationHours,$jobType,string $correlationId=""):GardenJob{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("garden:job:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("garden:job:".auth()->id(),3600);
    return DB::transaction(function()use($professionalId,$jobDate,$durationHours,$jobType,$correlationId){$p=GardenProfessional::findOrFail($professionalId);$total=(int)($p->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'garden_job','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$j=GardenJob::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'professional_id'=>$professionalId,'customer_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','job_date'=>$jobDate,'duration_hours'=>$durationHours,'job_type'=>$jobType,'tags'=>['garden'=>true]]);Log::channel('audit')->info('Garden job created',['job_id'=>$j->id,'correlation_id'=>$correlationId]);return $j;});
    }
    public function completeJob(int $jobId,string $correlationId=""):GardenJob{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($jobId,$correlationId){$j=GardenJob::findOrFail($jobId);if($j->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$j->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$j->payout_kopecks,'garden_payout',['correlation_id'=>$correlationId,'job_id'=>$j->id]);Log::channel('audit')->info('Garden job completed',['job_id'=>$j->id]);return $j;});}
    public function cancelJob(int $jobId,string $correlationId=""):GardenJob{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($jobId,$correlationId){$j=GardenJob::findOrFail($jobId);if($j->status==='completed')throw new \RuntimeException("Cannot cancel",400);$j->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($j->payment_status==='completed')$this->wallet->credit(tenant()->id,$j->total_kopecks,'garden_refund',['correlation_id'=>$correlationId,'job_id'=>$j->id]);Log::channel('audit')->info('Garden job cancelled',['job_id'=>$j->id]);return $j;});}
    public function getJob(int $jobId):GardenJob{return GardenJob::findOrFail($jobId);}
    public function getUserJobs(int $customerId){return GardenJob::where('customer_id',$customerId)->orderBy('created_at','desc')->take(10)->get();}
}

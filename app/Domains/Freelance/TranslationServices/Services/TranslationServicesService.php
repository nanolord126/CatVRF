<?php declare(strict_types=1);

namespace App\Domains\Freelance\TranslationServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TranslationServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createJob(int $translatorId,$languagePair,$wordCount,$deliveryDate,string $correlationId=""):TranslationJob{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("trans:job:".auth()->id(),14))throw new \RuntimeException("Too many",429);RateLimiter::hit("trans:job:".auth()->id(),3600);
    return DB::transaction(function()use($translatorId,$languagePair,$wordCount,$deliveryDate,$correlationId){$t=Translator::findOrFail($translatorId);$total=(int)($t->price_kopecks_per_word*$wordCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'translation','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$j=TranslationJob::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'translator_id'=>$translatorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','language_pair'=>$languagePair,'word_count'=>$wordCount,'delivery_date'=>$deliveryDate,'tags'=>['translation'=>true]]);Log::channel('audit')->info('Translation job created',['job_id'=>$j->id,'correlation_id'=>$correlationId]);return $j;});
    }
    public function completeJob(int $jobId,string $correlationId=""):TranslationJob{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($jobId,$correlationId){$j=TranslationJob::findOrFail($jobId);if($j->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$j->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$j->payout_kopecks,'trans_payout',['correlation_id'=>$correlationId,'job_id'=>$j->id]);Log::channel('audit')->info('Translation job completed',['job_id'=>$j->id]);return $j;});}
    public function cancelJob(int $jobId,string $correlationId=""):TranslationJob{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($jobId,$correlationId){$j=TranslationJob::findOrFail($jobId);if($j->status==='completed')throw new \RuntimeException("Cannot cancel",400);$j->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($j->payment_status==='completed')$this->wallet->credit(tenant()->id,$j->total_kopecks,'trans_refund',['correlation_id'=>$correlationId,'job_id'=>$j->id]);Log::channel('audit')->info('Translation job cancelled',['job_id'=>$j->id]);return $j;});}
    public function getJob(int $jobId):TranslationJob{return TranslationJob::findOrFail($jobId);}
    public function getUserJobs(int $clientId){return TranslationJob::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

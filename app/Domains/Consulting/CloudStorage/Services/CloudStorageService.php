<?php declare(strict_types=1);

namespace App\Domains\Consulting\CloudStorage\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CloudStorageService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createSubscription(int $providerId,$planType,$monthCount,string $correlationId=""):StorageSubscription{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("storage:sub:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("storage:sub:".auth()->id(),3600);
    return DB::transaction(function()use($providerId,$planType,$monthCount,$correlationId){$p=StorageProvider::findOrFail($providerId);$total=(int)($p->price_kopecks_per_month*$monthCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'cloud_storage','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=StorageSubscription::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'provider_id'=>$providerId,'user_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','plan_type'=>$planType,'start_date'=>now(),'end_date'=>now()->addMonths($monthCount),'storage_gb'=>$p->storage_gb,'tags'=>['cloud'=>true]]);Log::channel('audit')->info('Cloud storage subscription created',['subscription_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
    }
    public function completeSubscription(int $subscriptionId,string $correlationId=""):StorageSubscription{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($subscriptionId,$correlationId){$s=StorageSubscription::findOrFail($subscriptionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'storage_payout',['correlation_id'=>$correlationId,'subscription_id'=>$s->id]);Log::channel('audit')->info('Cloud storage subscription activated',['subscription_id'=>$s->id]);return $s;});}
    public function cancelSubscription(int $subscriptionId,string $correlationId=""):StorageSubscription{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($subscriptionId,$correlationId){$s=StorageSubscription::findOrFail($subscriptionId);if($s->status==='active')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'storage_refund',['correlation_id'=>$correlationId,'subscription_id'=>$s->id]);Log::channel('audit')->info('Cloud storage subscription cancelled',['subscription_id'=>$s->id]);return $s;});}
    public function getSubscription(int $subscriptionId):StorageSubscription{return StorageSubscription::findOrFail($subscriptionId);}
    public function getUserSubscriptions(int $userId){return StorageSubscription::where('user_id',$userId)->orderBy('created_at','desc')->take(10)->get();}
}

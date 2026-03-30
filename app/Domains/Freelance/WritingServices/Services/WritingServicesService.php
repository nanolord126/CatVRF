<?php declare(strict_types=1);

namespace App\Domains\Freelance\WritingServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WritingServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createOrder(int $writerId,$projectType,$wordCount,$dueDate,string $correlationId=""):WritingOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("writing:order:".auth()->id(),13))throw new \RuntimeException("Too many",429);RateLimiter::hit("writing:order:".auth()->id(),3600);
    return DB::transaction(function()use($writerId,$projectType,$wordCount,$dueDate,$correlationId){$w=Writer::findOrFail($writerId);$total=(int)($w->price_kopecks_per_word*$wordCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'writing_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=WritingOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'writer_id'=>$writerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'word_count'=>$wordCount,'due_date'=>$dueDate,'tags'=>['writing'=>true]]);Log::channel('audit')->info('Writing order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
    }
    public function completeOrder(int $orderId,string $correlationId=""):WritingOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=WritingOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'writing_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Writing order completed',['order_id'=>$o->id]);return $o;});}
    public function cancelOrder(int $orderId,string $correlationId=""):WritingOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=WritingOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'writing_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Writing order cancelled',['order_id'=>$o->id]);return $o;});}
    public function getOrder(int $orderId):WritingOrder{return WritingOrder::findOrFail($orderId);}
    public function getUserOrders(int $clientId){return WritingOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);

namespace App\Domains\PartySupplies\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PartySuppliesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createOrder(int $vendorId,$items,$deliveryDate,string $correlationId=""):PartyOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("party:order:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("party:order:".auth()->id(),3600);
    return DB::transaction(function()use($vendorId,$items,$deliveryDate,$correlationId){$v=PartyVendor::findOrFail($vendorId);$total=0;foreach($items as $item){$total+=$item['price_kopecks']*($item['qty']??1);}$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'party_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=PartyOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'vendor_id'=>$vendorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','items'=>$items,'delivery_date'=>$deliveryDate,'tags'=>['party'=>true]]);Log::channel('audit')->info('Party order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
    }
    public function completeOrder(int $orderId,string $correlationId=""):PartyOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=PartyOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'party_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Party order completed',['order_id'=>$o->id]);return $o;});}
    public function cancelOrder(int $orderId,string $correlationId=""):PartyOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=PartyOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'party_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Party order cancelled',['order_id'=>$o->id]);return $o;});}
    public function getOrder(int $orderId):PartyOrder{return PartyOrder::findOrFail($orderId);}
    public function getUserOrders(int $clientId){return PartyOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);

namespace App\Domains\Archived\PartySupplies\Gifts\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GiftsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}


        public function createOrder(int $sellerId,array $items,string $correlationId=""):GiftOrder{


            $correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("gifts:order:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("gifts:order:".auth()->id(),3600);


            return DB::transaction(function()use($sellerId,$items,$correlationId){$total=0;foreach($items as $item){$g=GiftProduct::where('id',$item['product_id'])->firstOrFail();$total+=$g->price_kopecks*$item['quantity'];if($g->stock<$item['quantity'])throw new \RuntimeException("Out of stock",400);}


                $fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'gift_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block'){Log::channel('audit')->error('Gift order blocked',['user_id'=>auth()->id(),'correlation_id'=>$correlationId]);throw new \RuntimeException("Security block",403);}


                $o=GiftOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'seller_id'=>$sellerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','items_json'=>$items,'tags'=>['gifts'=>true]]);Log::channel('audit')->info('Gift order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});


        }


        public function completeOrder(int $orderId,string $correlationId=""):GiftOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=GiftOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);foreach($o->items_json as $i){GiftProduct::findOrFail($i['product_id'])->decrement('stock',$i['quantity']);}$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'gift_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Gift completed',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});}


        public function cancelOrder(int $orderId,string $correlationId=""):GiftOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=GiftOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed'){$this->wallet->credit(tenant()->id,$o->total_kopecks,'gift_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);}Log::channel('audit')->info('Gift cancelled',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});}


        public function getOrder(int $orderId):GiftOrder{return GiftOrder::findOrFail($orderId);}


        public function getUserOrders(int $clientId){return GiftOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

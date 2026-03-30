<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\MedicalSupplies\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalSuppliesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
        public function createOrder(int $pharmacyId,array $items,string $correlationId=""):MedicineOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("med:order:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("med:order:".auth()->id(),3600);
            return DB::transaction(function()use($pharmacyId,$items,$correlationId){$total=0;foreach($items as $item){$m=Medicine::where('id',$item['medicine_id'])->firstOrFail();$total+=$m->price_kopecks*$item['quantity'];if($m->stock<$item['quantity'])throw new \RuntimeException("Out of stock",400);}$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'medicine_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block'){Log::channel('audit')->error('Medicine order blocked',['user_id'=>auth()->id(),'correlation_id'=>$correlationId]);throw new \RuntimeException("Security",403);}$o=MedicineOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'pharmacy_id'=>$pharmacyId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','items_json'=>$items,'tags'=>['medical'=>true]]);Log::channel('audit')->info('Medicine order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
        }
        public function completeOrder(int $orderId,string $correlationId=""):MedicineOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=MedicineOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);foreach($o->items_json as $i){Medicine::findOrFail($i['medicine_id'])->decrement('stock',$i['quantity']);}$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'med_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Medicine completed',['order_id'=>$o->id]);return $o;});}
        public function cancelOrder(int $orderId,string $correlationId=""):MedicineOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=MedicineOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed'){$this->wallet->credit(tenant()->id,$o->total_kopecks,'med_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);}Log::channel('audit')->info('Medicine cancelled',['order_id'=>$o->id]);return $o;});}
        public function getOrder(int $orderId):MedicineOrder{return MedicineOrder::findOrFail($orderId);}
        public function getUserOrders(int $clientId){return MedicineOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

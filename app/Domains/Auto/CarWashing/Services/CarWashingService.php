<?php

declare(strict_types=1);

namespace App\Domains\Auto\CarWashing\Services;
use App\Domains\Auto\CarWashing\Models\CarWashStation;
use App\Domains\Auto\CarWashing\Models\WashingOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * CarWashingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CarWashingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createOrder(int $stationId,$bookingDate,$serviceType,string $correlationId=""):WashingOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("carwash:order:".auth()->id(),30))throw new \RuntimeException("Too many",429);RateLimiter::hit("carwash:order:".auth()->id(),3600);
return DB::transaction(function()use($stationId,$bookingDate,$serviceType,$correlationId){$s=CarWashStation::findOrFail($stationId);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'car_washing','correlation_id'=>$correlationId,'amount'=>$s->price_kopecks_per_service]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=WashingOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'station_id'=>$stationId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$s->price_kopecks_per_service,'payout_kopecks'=>$s->price_kopecks_per_service-(int)($s->price_kopecks_per_service*0.14),'payment_status'=>'pending','booking_date'=>$bookingDate,'service_type'=>$serviceType,'tags'=>['carwash'=>true]]);Log::channel('audit')->info('Car wash order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):WashingOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=WashingOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'carwash_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Car wash completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):WashingOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=WashingOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'carwash_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Car wash cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):WashingOrder{return WashingOrder::findOrFail($orderId);}
public function getUserOrders(int $clientId){return WashingOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

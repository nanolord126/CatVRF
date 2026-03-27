<?php

declare(strict_types=1);

namespace App\Domains\CleaningServices\CleaningServices\Cleaning\Services;
use App\Domains\CleaningServices\CleaningServices\Cleaning\Models\CleaningService as CleaningServiceModel;
use App\Domains\CleaningServices\CleaningServices\Cleaning\Models\CleaningOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * CleaningServiceService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CleaningServiceService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createOrder(int $serviceId,$orderDate,$durationHours,$areaSqm,string $correlationId=""):CleaningOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("cleaning:order:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("cleaning:order:".auth()->id(),3600);
return DB::transaction(function()use($serviceId,$orderDate,$durationHours,$areaSqm,$correlationId){$s=CleaningServiceModel::findOrFail($serviceId);$total=(int)($s->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'cleaning_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=CleaningOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'service_id'=>$serviceId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','order_date'=>$orderDate,'duration_hours'=>$durationHours,'area_sqm'=>$areaSqm,'tags'=>['cleaning'=>true]]);Log::channel('audit')->info('Cleaning order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):CleaningOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=CleaningOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'cleaning_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Cleaning order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):CleaningOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=CleaningOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'cleaning_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Cleaning order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):CleaningOrder{return CleaningOrder::findOrFail($orderId);}
public function getUserOrders(int $clientId){return CleaningOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

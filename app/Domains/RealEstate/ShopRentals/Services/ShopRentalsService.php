<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\ShopRentals\Services;
use App\Domains\RealEstate\ShopRentals\Models\Storefront;
use App\Domains\RealEstate\ShopRentals\Models\StorefrontRental;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * ShopRentalsService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShopRentalsService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createRental(int $storefrontId,$leaseStart,$leaseEnd,$monthCount,string $correlationId=""):StorefrontRental{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("shop:rental:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("shop:rental:".auth()->id(),3600);
return DB::transaction(function()use($storefrontId,$leaseStart,$leaseEnd,$monthCount,$correlationId){$s=Storefront::findOrFail($storefrontId);$total=(int)($s->price_kopecks_per_month*$monthCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'shop_rental','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$r=StorefrontRental::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'storefront_id'=>$storefrontId,'tenant_business_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','lease_start'=>$leaseStart,'lease_end'=>$leaseEnd,'tags'=>['shop'=>true]]);Log::channel('audit')->info('Storefront rental created',['rental_id'=>$r->id,'correlation_id'=>$correlationId]);return $r;});
}
public function completeRental(int $rentalId,string $correlationId=""):StorefrontRental{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($rentalId,$correlationId){$r=StorefrontRental::findOrFail($rentalId);if($r->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$r->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$r->payout_kopecks,'shop_payout',['correlation_id'=>$correlationId,'rental_id'=>$r->id]);Log::channel('audit')->info('Storefront rental activated',['rental_id'=>$r->id]);return $r;});}
public function cancelRental(int $rentalId,string $correlationId=""):StorefrontRental{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($rentalId,$correlationId){$r=StorefrontRental::findOrFail($rentalId);if($r->status==='active')throw new \RuntimeException("Cannot cancel",400);$r->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($r->payment_status==='completed')$this->wallet->credit(tenant()->id,$r->total_kopecks,'shop_refund',['correlation_id'=>$correlationId,'rental_id'=>$r->id]);Log::channel('audit')->info('Storefront rental cancelled',['rental_id'=>$r->id]);return $r;});}
public function getRental(int $rentalId):StorefrontRental{return StorefrontRental::findOrFail($rentalId);}
public function getUserRentals(int $tenantBusinessId){return StorefrontRental::where('tenant_business_id',$tenantBusinessId)->orderBy('created_at','desc')->take(10)->get();}
}

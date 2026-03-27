<?php

declare(strict_types=1);

namespace App\Domains\Education\KidsCenters\Services;
use App\Domains\Education\KidsCenters\Models\KidsCenter;
use App\Domains\Education\KidsCenters\Models\KidsBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * KidsCentersService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class KidsCentersService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createBooking(int $centerId,$bookingDate,$durationHours,$kidsCount,string $correlationId=""):KidsBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("kids:book:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("kids:book:".auth()->id(),3600);
return DB::transaction(function()use($centerId,$bookingDate,$durationHours,$kidsCount,$correlationId){$c=KidsCenter::findOrFail($centerId);$total=(int)($c->price_kopecks_per_hour*$durationHours*$kidsCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'kids_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=KidsBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'center_id'=>$centerId,'parent_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','booking_date'=>$bookingDate,'duration_hours'=>$durationHours,'kids_count'=>$kidsCount,'tags'=>['kids_center'=>true]]);Log::channel('audit')->info('Kids center booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeBooking(int $bookingId,string $correlationId=""):KidsBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=KidsBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'kids_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Kids center completed',['booking_id'=>$b->id]);return $b;});}
public function cancelBooking(int $bookingId,string $correlationId=""):KidsBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=KidsBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'kids_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Kids center cancelled',['booking_id'=>$b->id]);return $b;});}
public function getBooking(int $bookingId):KidsBooking{return KidsBooking::findOrFail($bookingId);}
public function getUserBookings(int $parentId){return KidsBooking::where('parent_id',$parentId)->orderBy('created_at','desc')->take(10)->get();}
}

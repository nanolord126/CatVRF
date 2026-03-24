<?php declare(strict_types=1);
namespace App\Domains\BathsSaunas\Services;
use App\Domains\BathsSaunas\Models\Bathhouse;
use App\Domains\BathsSaunas\Models\BathBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class BathsSaunasService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createBooking(int $bathId,$bookingDate,$durationHours,$bathType,string $correlationId=""):BathBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("bath:book:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("bath:book:".auth()->id(),3600);
return DB::transaction(function()use($bathId,$bookingDate,$durationHours,$bathType,$correlationId){$b=Bathhouse::findOrFail($bathId);$total=(int)($b->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'bath_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$bk=BathBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'bath_id'=>$bathId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','booking_date'=>$bookingDate,'duration_hours'=>$durationHours,'bath_type'=>$bathType,'tags'=>['bath'=>true]]);Log::channel('audit')->info('Bath booking created',['booking_id'=>$bk->id,'correlation_id'=>$correlationId]);return $bk;});
}
public function completeBooking(int $bookingId,string $correlationId=""):BathBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=BathBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'bath_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Bath booking completed',['booking_id'=>$b->id]);return $b;});}
public function cancelBooking(int $bookingId,string $correlationId=""):BathBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=BathBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'bath_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Bath booking cancelled',['booking_id'=>$b->id]);return $b;});}
public function getBooking(int $bookingId):BathBooking{return BathBooking::findOrFail($bookingId);}
public function getUserBookings(int $clientId){return BathBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

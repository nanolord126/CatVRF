<?php

declare(strict_types=1);

namespace App\Domains\Tickets\EntertainmentBooking\Services;
use App\Domains\Tickets\EntertainmentBooking\Models\Entertainer;
use App\Domains\Tickets\EntertainmentBooking\Models\EntertainmentBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * EntertainmentBookingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EntertainmentBookingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createBooking(int $entertainerId,$entertainmentType,$durationHours,$eventDate,string $correlationId=""):EntertainmentBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("ent:book:".auth()->id(),17))throw new \RuntimeException("Too many",429);RateLimiter::hit("ent:book:".auth()->id(),3600);
return DB::transaction(function()use($entertainerId,$entertainmentType,$durationHours,$eventDate,$correlationId){$e=Entertainer::findOrFail($entertainerId);$total=(int)($e->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'entertainment','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=EntertainmentBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'entertainer_id'=>$entertainerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','entertainment_type'=>$entertainmentType,'duration_hours'=>$durationHours,'event_date'=>$eventDate,'tags'=>['entertainment'=>true]]);Log::channel('audit')->info('Entertainment booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeBooking(int $bookingId,string $correlationId=""):EntertainmentBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=EntertainmentBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'ent_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Entertainment booking completed',['booking_id'=>$b->id]);return $b;});}
public function cancelBooking(int $bookingId,string $correlationId=""):EntertainmentBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=EntertainmentBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'ent_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Entertainment booking cancelled',['booking_id'=>$b->id]);return $b;});}
public function getBooking(int $bookingId):EntertainmentBooking{return EntertainmentBooking::findOrFail($bookingId);}
public function getUserBookings(int $clientId){return EntertainmentBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

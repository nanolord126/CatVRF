declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\HotelManagement\Services;
use App\Domains\HotelManagement\Models\Hotel;
use App\Domains\HotelManagement\Models\HotelBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * HotelManagementService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class HotelManagementService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createBooking(int $hotelId,$roomType,$checkIn,$checkOut,string $correlationId=""):HotelBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("hotel:book:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("hotel:book:".auth()->id(),3600);
return $this->db->transaction(function()use($hotelId,$roomType,$checkIn,$checkOut,$correlationId){$h=Hotel::findOrFail($hotelId);$nights=(int)((\strtotime($checkOut)-\strtotime($checkIn))/86400);$total=(int)($h->price_kopecks_per_night*$nights);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'hotel_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=HotelBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'hotel_id'=>$hotelId,'guest_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','room_type'=>$roomType,'check_in'=>$checkIn,'check_out'=>$checkOut,'nights_count'=>$nights,'tags'=>['hotel'=>true]]);$this->log->channel('audit')->info('Hotel booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeBooking(int $bookingId,string $correlationId=""):HotelBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=HotelBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'hotel_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Hotel booking completed',['booking_id'=>$b->id]);return $b;});}
public function cancelBooking(int $bookingId,string $correlationId=""):HotelBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=HotelBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'hotel_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Hotel booking cancelled',['booking_id'=>$b->id]);return $b;});}
public function getBooking(int $bookingId):HotelBooking{return HotelBooking::findOrFail($bookingId);}
public function getUserBookings(int $guestId){return HotelBooking::where('guest_id',$guestId)->orderBy('created_at','desc')->take(10)->get();}
}

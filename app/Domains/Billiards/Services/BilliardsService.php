declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\Billiards\Services;
use App\Domains\Billiards\Models\BilliardHall;
use App\Domains\Billiards\Models\BilliardBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * BilliardsService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BilliardsService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createBooking(int $hallId,$bookingDate,$durationHours,$tableNumber,string $correlationId=""):BilliardBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("billiards:book:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("billiards:book:".auth()->id(),3600);
return $this->db->transaction(function()use($hallId,$bookingDate,$durationHours,$tableNumber,$correlationId){$h=BilliardHall::findOrFail($hallId);$total=(int)($h->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'billiard_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=BilliardBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'hall_id'=>$hallId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','booking_date'=>$bookingDate,'duration_hours'=>$durationHours,'table_number'=>$tableNumber,'tags'=>['billiards'=>true]]);$this->log->channel('audit')->info('Billiard booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeBooking(int $bookingId,string $correlationId=""):BilliardBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=BilliardBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'billiards_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Billiard booking completed',['booking_id'=>$b->id]);return $b;});}
public function cancelBooking(int $bookingId,string $correlationId=""):BilliardBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=BilliardBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'billiards_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Billiard booking cancelled',['booking_id'=>$b->id]);return $b;});}
public function getBooking(int $bookingId):BilliardBooking{return BilliardBooking::findOrFail($bookingId);}
public function getUserBookings(int $clientId){return BilliardBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

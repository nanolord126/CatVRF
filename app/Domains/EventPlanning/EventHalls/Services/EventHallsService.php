<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\EventHalls\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventHallsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createBooking(int $hallId,$bookingDate,$durationHours,$eventType,string $correlationId=""):HallBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("eventhall:book:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("eventhall:book:".auth()->id(),3600);
    return DB::transaction(function()use($hallId,$bookingDate,$durationHours,$eventType,$correlationId){$h=EventHall::findOrFail($hallId);$total=(int)($h->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'eventhall_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=HallBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'hall_id'=>$hallId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','booking_date'=>$bookingDate,'duration_hours'=>$durationHours,'event_type'=>$eventType,'tags'=>['eventhall'=>true]]);Log::channel('audit')->info('Event hall booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
    }
    public function completeBooking(int $bookingId,string $correlationId=""):HallBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=HallBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'eventhall_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Event hall booking completed',['booking_id'=>$b->id]);return $b;});}
    public function cancelBooking(int $bookingId,string $correlationId=""):HallBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=HallBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'eventhall_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Event hall booking cancelled',['booking_id'=>$b->id]);return $b;});}
    public function getBooking(int $bookingId):HallBooking{return HallBooking::findOrFail($bookingId);}
    public function getUserBookings(int $clientId){return HallBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

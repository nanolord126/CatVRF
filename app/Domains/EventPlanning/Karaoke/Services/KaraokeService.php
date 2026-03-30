<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Karaoke\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KaraokeService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createBooking(int $clubId,$bookingDate,$durationHours,$roomNumber,string $correlationId=""):KaraokeBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("karaoke:book:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("karaoke:book:".auth()->id(),3600);
    return DB::transaction(function()use($clubId,$bookingDate,$durationHours,$roomNumber,$correlationId){$c=KaraokeClub::findOrFail($clubId);$total=(int)($c->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'karaoke_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=KaraokeBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'club_id'=>$clubId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','booking_date'=>$bookingDate,'duration_hours'=>$durationHours,'room_number'=>$roomNumber,'tags'=>['karaoke'=>true]]);Log::channel('audit')->info('Karaoke booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
    }
    public function completeBooking(int $bookingId,string $correlationId=""):KaraokeBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=KaraokeBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'karaoke_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Karaoke booking completed',['booking_id'=>$b->id]);return $b;});}
    public function cancelBooking(int $bookingId,string $correlationId=""):KaraokeBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=KaraokeBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'karaoke_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Karaoke booking cancelled',['booking_id'=>$b->id]);return $b;});}
    public function getBooking(int $bookingId):KaraokeBooking{return KaraokeBooking::findOrFail($bookingId);}
    public function getUserBookings(int $clientId){return KaraokeBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

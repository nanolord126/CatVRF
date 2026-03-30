<?php declare(strict_types=1);

namespace App\Domains\Hotels\Lodging\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LodgingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createBooking(int $lodgeId,$checkIn,$checkOut,string $correlationId=""):LodgingBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("lodging:book:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("lodging:book:".auth()->id(),3600);
    return DB::transaction(function()use($lodgeId,$checkIn,$checkOut,$correlationId){$lodge=Lodge::findOrFail($lodgeId);$nights=(strtotime($checkOut)-strtotime($checkIn))/86400;$total=(int)($lodge->price_kopecks_per_night*$nights);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'lodging_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=LodgingBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'lodge_id'=>$lodgeId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','check_in'=>$checkIn,'check_out'=>$checkOut,'tags'=>['lodging'=>true]]);Log::channel('audit')->info('Lodging booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
    }
    public function completeBooking(int $bookingId,string $correlationId=""):LodgingBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=LodgingBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'lodging_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Lodging completed',['booking_id'=>$b->id]);return $b;});}
    public function cancelBooking(int $bookingId,string $correlationId=""):LodgingBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=LodgingBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'lodging_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Lodging cancelled',['booking_id'=>$b->id]);return $b;});}
    public function getBooking(int $bookingId):LodgingBooking{return LodgingBooking::findOrFail($bookingId);}
    public function getUserBookings(int $clientId){return LodgingBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

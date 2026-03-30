<?php declare(strict_types=1);

namespace App\Domains\Archived\CarRental\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarRentalService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}


    public function createBooking(int $carId,$pickupDate,$returnDate,string $correlationId=""):RentalBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("rental:book:".auth()->id(),9))throw new \RuntimeException("Too many",429);RateLimiter::hit("rental:book:".auth()->id(),3600);


    return DB::transaction(function()use($carId,$pickupDate,$returnDate,$correlationId){$c=RentalCar::findOrFail($carId);$days=((strtotime($returnDate)-strtotime($pickupDate))/86400);$total=(int)($c->price_kopecks_per_day*$days);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'car_rental','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=RentalBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'car_id'=>$carId,'renter_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','pickup_date'=>$pickupDate,'return_date'=>$returnDate,'days_count'=>(int)$days,'tags'=>['rental'=>true]]);Log::channel('audit')->info('Car rental booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});


    }


    public function completeBooking(int $bookingId,string $correlationId=""):RentalBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=RentalBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'rental_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Car rental booking activated',['booking_id'=>$b->id]);return $b;});}


    public function cancelBooking(int $bookingId,string $correlationId=""):RentalBooking{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($bookingId,$correlationId){$b=RentalBooking::findOrFail($bookingId);if($b->status==='active')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'rental_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);Log::channel('audit')->info('Car rental booking cancelled',['booking_id'=>$b->id]);return $b;});}


    public function getBooking(int $bookingId):RentalBooking{return RentalBooking::findOrFail($bookingId);}


    public function getUserBookings(int $renterId){return RentalBooking::where('renter_id',$renterId)->orderBy('created_at','desc')->take(10)->get();}
}

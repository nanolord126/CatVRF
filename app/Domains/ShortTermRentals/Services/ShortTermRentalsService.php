<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShortTermRentalsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createRental(int $apartmentId,$checkIn,$checkOut,string $correlationId=""):ApartmentRental{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("str:rental:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("str:rental:".auth()->id(),3600);
    return DB::transaction(function()use($apartmentId,$checkIn,$checkOut,$correlationId){$a=Apartment::findOrFail($apartmentId);$nights=(strtotime($checkOut)-strtotime($checkIn))/86400;$total=(int)($a->price_kopecks_per_night*$nights);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'str_rental','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$r=ApartmentRental::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'apartment_id'=>$apartmentId,'guest_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','check_in'=>$checkIn,'check_out'=>$checkOut,'tags'=>['str'=>true]]);Log::channel('audit')->info('Apartment rental created',['rental_id'=>$r->id,'correlation_id'=>$correlationId]);return $r;});
    }
    public function completeRental(int $rentalId,string $correlationId=""):ApartmentRental{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($rentalId,$correlationId){$r=ApartmentRental::findOrFail($rentalId);if($r->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$r->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$r->payout_kopecks,'str_payout',['correlation_id'=>$correlationId,'rental_id'=>$r->id]);Log::channel('audit')->info('Apartment rental completed',['rental_id'=>$r->id]);return $r;});}
    public function cancelRental(int $rentalId,string $correlationId=""):ApartmentRental{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($rentalId,$correlationId){$r=ApartmentRental::findOrFail($rentalId);if($r->status==='completed')throw new \RuntimeException("Cannot cancel",400);$r->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($r->payment_status==='completed')$this->wallet->credit(tenant()->id,$r->total_kopecks,'str_refund',['correlation_id'=>$correlationId,'rental_id'=>$r->id]);Log::channel('audit')->info('Apartment rental cancelled',['rental_id'=>$r->id]);return $r;});}
    public function getRental(int $rentalId):ApartmentRental{return ApartmentRental::findOrFail($rentalId);}
    public function getUserRentals(int $guestId){return ApartmentRental::where('guest_id',$guestId)->orderBy('created_at','desc')->take(10)->get();}
}

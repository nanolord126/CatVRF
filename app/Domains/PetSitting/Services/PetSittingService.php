declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\PetSitting\Services;
use App\Domains\PetSitting\Models\PetSitter;
use App\Domains\PetSitting\Models\SittingBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * PetSittingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PetSittingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createBooking(int $sitterId,$startDate,$endDate,$petNames,string $correlationId=""):SittingBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("petsit:book:".auth()->id(),14))throw new \RuntimeException("Too many",429);RateLimiter::hit("petsit:book:".auth()->id(),3600);
return $this->db->transaction(function()use($sitterId,$startDate,$endDate,$petNames,$correlationId){$s=PetSitter::findOrFail($sitterId);$hours=((strtotime($endDate)-strtotime($startDate))/3600);$total=(int)($s->price_kopecks_per_hour*$hours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'petsit_booking','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=SittingBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'sitter_id'=>$sitterId,'owner_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','start_date'=>$startDate,'end_date'=>$endDate,'pet_names'=>$petNames,'tags'=>['petsit'=>true]]);$this->log->channel('audit')->info('Pet sitting booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeBooking(int $bookingId,string $correlationId=""):SittingBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=SittingBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'petsit_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Pet sitting booking completed',['booking_id'=>$b->id]);return $b;});}
public function cancelBooking(int $bookingId,string $correlationId=""):SittingBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=SittingBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'petsit_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Pet sitting booking cancelled',['booking_id'=>$b->id]);return $b;});}
public function getBooking(int $bookingId):SittingBooking{return SittingBooking::findOrFail($bookingId);}
public function getUserBookings(int $ownerId){return SittingBooking::where('owner_id',$ownerId)->orderBy('created_at','desc')->take(10)->get();}
}

declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\SpaWellness\Services;
use App\Domains\SpaWellness\Models\SpaCenter;
use App\Domains\SpaWellness\Models\SpaBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * SpaWellnessService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SpaWellnessService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createBooking(int $spaId,$treatmentType,$durationMinutes,$bookingDate,string $correlationId=""):SpaBooking{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("spa:book:".auth()->id(),22))throw new \RuntimeException("Too many",429);RateLimiter::hit("spa:book:".auth()->id(),3600);
return $this->db->transaction(function()use($spaId,$treatmentType,$durationMinutes,$bookingDate,$correlationId){$s=SpaCenter::findOrFail($spaId);$total=(int)($s->price_kopecks_per_minute*$durationMinutes);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'spa_wellness','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=SpaBooking::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'spa_id'=>$spaId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','treatment_type'=>$treatmentType,'duration_minutes'=>$durationMinutes,'booking_date'=>$bookingDate,'tags'=>['spa'=>true]]);$this->log->channel('audit')->info('Spa booking created',['booking_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeBooking(int $bookingId,string $correlationId=""):SpaBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=SpaBooking::findOrFail($bookingId);if($b->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$b->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$b->payout_kopecks,'spa_payout',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Spa booking completed',['booking_id'=>$b->id]);return $b;});}
public function cancelBooking(int $bookingId,string $correlationId=""):SpaBooking{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bookingId,$correlationId){$b=SpaBooking::findOrFail($bookingId);if($b->status==='completed')throw new \RuntimeException("Cannot cancel",400);$b->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($b->payment_status==='completed')$this->wallet->credit(tenant()->id,$b->total_kopecks,'spa_refund',['correlation_id'=>$correlationId,'booking_id'=>$b->id]);$this->log->channel('audit')->info('Spa booking cancelled',['booking_id'=>$b->id]);return $b;});}
public function getBooking(int $bookingId):SpaBooking{return SpaBooking::findOrFail($bookingId);}
public function getUserBookings(int $clientId){return SpaBooking::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

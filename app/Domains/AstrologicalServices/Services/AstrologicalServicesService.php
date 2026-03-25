declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\AstrologicalServices\Services;
use App\Domains\AstrologicalServices\Models\Astrologer;
use App\Domains\AstrologicalServices\Models\AstrologyReading;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * AstrologicalServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AstrologicalServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createReading(int $astrologerId,$readingType,$readingHours,$readingDate,string $correlationId=""):AstrologyReading{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("astro:read:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("astro:read:".auth()->id(),3600);
return $this->db->transaction(function()use($astrologerId,$readingType,$readingHours,$readingDate,$correlationId){$a=Astrologer::findOrFail($astrologerId);$total=(int)($a->price_kopecks_per_hour*$readingHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'astrology','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$r=AstrologyReading::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'astrologer_id'=>$astrologerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','reading_type'=>$readingType,'reading_hours'=>$readingHours,'reading_date'=>$readingDate,'tags'=>['astrology'=>true]]);$this->log->channel('audit')->info('Astrology reading created',['reading_id'=>$r->id,'correlation_id'=>$correlationId]);return $r;});
}
public function completeReading(int $readingId,string $correlationId=""):AstrologyReading{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($readingId,$correlationId){$r=AstrologyReading::findOrFail($readingId);if($r->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$r->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$r->payout_kopecks,'astro_payout',['correlation_id'=>$correlationId,'reading_id'=>$r->id]);$this->log->channel('audit')->info('Astrology reading completed',['reading_id'=>$r->id]);return $r;});}
public function cancelReading(int $readingId,string $correlationId=""):AstrologyReading{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($readingId,$correlationId){$r=AstrologyReading::findOrFail($readingId);if($r->status==='completed')throw new \RuntimeException("Cannot cancel",400);$r->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($r->payment_status==='completed')$this->wallet->credit(tenant()->id,$r->total_kopecks,'astro_refund',['correlation_id'=>$correlationId,'reading_id'=>$r->id]);$this->log->channel('audit')->info('Astrology reading cancelled',['reading_id'=>$r->id]);return $r;});}
public function getReading(int $readingId):AstrologyReading{return AstrologyReading::findOrFail($readingId);}
public function getUserReadings(int $clientId){return AstrologyReading::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

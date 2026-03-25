declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\VehicleDealing\Services;
use App\Domains\VehicleDealing\Models\Vehicle;
use App\Domains\VehicleDealing\Models\VehicleSale;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * VehicleDealingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class VehicleDealingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createSale(int $vehicleId,string $correlationId=""):VehicleSale{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("vehicle:sale:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("vehicle:sale:".auth()->id(),3600);
return $this->db->transaction(function()use($vehicleId,$correlationId){$v=Vehicle::findOrFail($vehicleId);$total=$v->price_kopecks;$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'vehicle_sale','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$s=VehicleSale::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'vehicle_id'=>$vehicleId,'buyer_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','tags'=>['vehicle'=>true]]);$this->log->channel('audit')->info('Vehicle sale created',['sale_id'=>$s->id,'correlation_id'=>$correlationId]);return $s;});
}
public function completeSale(int $saleId,string $correlationId=""):VehicleSale{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($saleId,$correlationId){$s=VehicleSale::findOrFail($saleId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$v=Vehicle::findOrFail($s->vehicle_id);$v->update(['status'=>'sold']);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'vehicle_payout',['correlation_id'=>$correlationId,'sale_id'=>$s->id]);$this->log->channel('audit')->info('Vehicle sale completed',['sale_id'=>$s->id]);return $s;});}
public function cancelSale(int $saleId,string $correlationId=""):VehicleSale{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($saleId,$correlationId){$s=VehicleSale::findOrFail($saleId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'vehicle_refund',['correlation_id'=>$correlationId,'sale_id'=>$s->id]);$this->log->channel('audit')->info('Vehicle sale cancelled',['sale_id'=>$s->id]);return $s;});}
public function getSale(int $saleId):VehicleSale{return VehicleSale::findOrFail($saleId);}
public function getUserSales(int $buyerId){return VehicleSale::where('buyer_id',$buyerId)->orderBy('created_at','desc')->take(10)->get();}
}

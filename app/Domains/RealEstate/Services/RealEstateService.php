declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\RealEstate\Services;
use App\Domains\RealEstate\Models\Agent;
use App\Domains\RealEstate\Models\RealEstateTransaction;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * RealEstateService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RealEstateService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createTransaction(int $agentId,$amount,$propertyAddress,$transactionType,string $correlationId=""):RealEstateTransaction{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("realestate:txn:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("realestate:txn:".auth()->id(),3600);
return $this->db->transaction(function()use($agentId,$amount,$propertyAddress,$transactionType,$correlationId){$a=Agent::findOrFail($agentId);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'realestate_transaction','correlation_id'=>$correlationId,'amount'=>$amount]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$t=RealEstateTransaction::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'agent_id'=>$agentId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$amount,'payout_kopecks'=>(int)($amount*(1-$a->price_commission_percent/100)),'payment_status'=>'pending','property_address'=>$propertyAddress,'transaction_type'=>$transactionType,'tags'=>['realestate'=>true]]);$this->log->channel('audit')->info('Real estate transaction created',['transaction_id'=>$t->id,'correlation_id'=>$correlationId]);return $t;});
}
public function completeTransaction(int $transactionId,string $correlationId=""):RealEstateTransaction{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($transactionId,$correlationId){$t=RealEstateTransaction::findOrFail($transactionId);if($t->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$t->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$t->payout_kopecks,'realestate_payout',['correlation_id'=>$correlationId,'transaction_id'=>$t->id]);$this->log->channel('audit')->info('Real estate transaction completed',['transaction_id'=>$t->id]);return $t;});}
public function cancelTransaction(int $transactionId,string $correlationId=""):RealEstateTransaction{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($transactionId,$correlationId){$t=RealEstateTransaction::findOrFail($transactionId);if($t->status==='completed')throw new \RuntimeException("Cannot cancel",400);$t->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($t->payment_status==='completed')$this->wallet->credit(tenant()->id,$t->total_kopecks,'realestate_refund',['correlation_id'=>$correlationId,'transaction_id'=>$t->id]);$this->log->channel('audit')->info('Real estate transaction cancelled',['transaction_id'=>$t->id]);return $t;});}
public function getTransaction(int $transactionId):RealEstateTransaction{return RealEstateTransaction::findOrFail($transactionId);}
public function getUserTransactions(int $clientId){return RealEstateTransaction::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

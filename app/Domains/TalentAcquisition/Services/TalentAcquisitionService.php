declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\TalentAcquisition\Services;
use App\Domains\TalentAcquisition\Models\RecruitmentSpecialist;
use App\Domains\TalentAcquisition\Models\RecruitmentCampaign;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * TalentAcquisitionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TalentAcquisitionService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createCampaign(int $specialistId,$campaignType,$hoursSpent,$dueDate,string $correlationId=""):RecruitmentCampaign{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("talent:camp:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("talent:camp:".auth()->id(),3600);
return $this->db->transaction(function()use($specialistId,$campaignType,$hoursSpent,$dueDate,$correlationId){$s=RecruitmentSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'talent','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=RecruitmentCampaign::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','campaign_type'=>$campaignType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['talent'=>true]]);$this->log->channel('audit')->info('Recruitment campaign created',['campaign_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
}
public function completeCampaign(int $campaignId,string $correlationId=""):RecruitmentCampaign{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($campaignId,$correlationId){$c=RecruitmentCampaign::findOrFail($campaignId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'talent_payout',['correlation_id'=>$correlationId,'campaign_id'=>$c->id]);$this->log->channel('audit')->info('Recruitment campaign completed',['campaign_id'=>$c->id]);return $c;});}
public function cancelCampaign(int $campaignId,string $correlationId=""):RecruitmentCampaign{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($campaignId,$correlationId){$c=RecruitmentCampaign::findOrFail($campaignId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'talent_refund',['correlation_id'=>$correlationId,'campaign_id'=>$c->id]);$this->log->channel('audit')->info('Recruitment campaign cancelled',['campaign_id'=>$c->id]);return $c;});}
public function getCampaign(int $campaignId):RecruitmentCampaign{return RecruitmentCampaign::findOrFail($campaignId);}
public function getUserCampaigns(int $clientId){return RecruitmentCampaign::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

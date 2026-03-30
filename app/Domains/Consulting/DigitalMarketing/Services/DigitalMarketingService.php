<?php declare(strict_types=1);

namespace App\Domains\Consulting\DigitalMarketing\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DigitalMarketingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createCampaign(int $consultantId,$campaignType,$projectHours,$dueDate,string $correlationId=""):MarketingCampaign{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("marketing:camp:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("marketing:camp:".auth()->id(),3600);
    return DB::transaction(function()use($consultantId,$campaignType,$projectHours,$dueDate,$correlationId){$c=MarketingConsultant::findOrFail($consultantId);$total=(int)($c->price_kopecks_per_hour*$projectHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'digital_marketing','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$camp=MarketingCampaign::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'consultant_id'=>$consultantId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','campaign_type'=>$campaignType,'project_hours'=>$projectHours,'due_date'=>$dueDate,'tags'=>['digital_mkt'=>true]]);Log::channel('audit')->info('Marketing campaign created',['campaign_id'=>$camp->id,'correlation_id'=>$correlationId]);return $camp;});
    }
    public function activateCampaign(int $campaignId,string $correlationId=""):MarketingCampaign{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($campaignId,$correlationId){$c=MarketingCampaign::findOrFail($campaignId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'mkt_payout',['correlation_id'=>$correlationId,'campaign_id'=>$c->id]);Log::channel('audit')->info('Marketing campaign activated',['campaign_id'=>$c->id]);return $c;});}
    public function cancelCampaign(int $campaignId,string $correlationId=""):MarketingCampaign{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($campaignId,$correlationId){$c=MarketingCampaign::findOrFail($campaignId);if($c->status==='active')throw new \RuntimeException("Cannot cancel active",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'mkt_refund',['correlation_id'=>$correlationId,'campaign_id'=>$c->id]);Log::channel('audit')->info('Marketing campaign cancelled',['campaign_id'=>$c->id]);return $c;});}
    public function getCampaign(int $campaignId):MarketingCampaign{return MarketingCampaign::findOrFail($campaignId);}
    public function getUserCampaigns(int $clientId){return MarketingCampaign::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);

namespace App\Domains\Logistics\SupplierRelationship\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SupplierRelationshipService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createEngagement(int $advisorId,$engagementType,$hoursSpent,$dueDate,string $correlationId=""):SRMEngagement{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("srm:eng:".auth()->id(),16))throw new \RuntimeException("Too many",429);RateLimiter::hit("srm:eng:".auth()->id(),3600);
    return DB::transaction(function()use($advisorId,$engagementType,$hoursSpent,$dueDate,$correlationId){$a=SRMAdvisor::findOrFail($advisorId);$total=(int)($a->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'srm','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=SRMEngagement::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'advisor_id'=>$advisorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','engagement_type'=>$engagementType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['srm'=>true]]);Log::channel('audit')->info('SRM engagement created',['engagement_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
    }
    public function completeEngagement(int $engagementId,string $correlationId=""):SRMEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=SRMEngagement::findOrFail($engagementId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'srm_payout',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('SRM engagement completed',['engagement_id'=>$e->id]);return $e;});}
    public function cancelEngagement(int $engagementId,string $correlationId=""):SRMEngagement{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($engagementId,$correlationId){$e=SRMEngagement::findOrFail($engagementId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'srm_refund',['correlation_id'=>$correlationId,'engagement_id'=>$e->id]);Log::channel('audit')->info('SRM engagement cancelled',['engagement_id'=>$e->id]);return $e;});}
    public function getEngagement(int $engagementId):SRMEngagement{return SRMEngagement::findOrFail($engagementId);}
    public function getUserEngagements(int $clientId){return SRMEngagement::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

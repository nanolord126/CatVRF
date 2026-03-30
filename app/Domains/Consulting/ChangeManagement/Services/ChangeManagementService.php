<?php declare(strict_types=1);

namespace App\Domains\Consulting\ChangeManagement\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ChangeManagementService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createInitiative(int $managerId,$initiativeType,$hoursSpent,$dueDate,string $correlationId=""):ChangeInitiative{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("change:init:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("change:init:".auth()->id(),3600);
    return DB::transaction(function()use($managerId,$initiativeType,$hoursSpent,$dueDate,$correlationId){$m=ChangeManager::findOrFail($managerId);$total=(int)($m->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'change','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=ChangeInitiative::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'manager_id'=>$managerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','initiative_type'=>$initiativeType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['change'=>true]]);Log::channel('audit')->info('Change initiative created',['initiative_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
    }
    public function completeInitiative(int $initiativeId,string $correlationId=""):ChangeInitiative{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($initiativeId,$correlationId){$c=ChangeInitiative::findOrFail($initiativeId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'change_payout',['correlation_id'=>$correlationId,'initiative_id'=>$c->id]);Log::channel('audit')->info('Change initiative completed',['initiative_id'=>$c->id]);return $c;});}
    public function cancelInitiative(int $initiativeId,string $correlationId=""):ChangeInitiative{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($initiativeId,$correlationId){$c=ChangeInitiative::findOrFail($initiativeId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'change_refund',['correlation_id'=>$correlationId,'initiative_id'=>$c->id]);Log::channel('audit')->info('Change initiative cancelled',['initiative_id'=>$c->id]);return $c;});}
    public function getInitiative(int $initiativeId):ChangeInitiative{return ChangeInitiative::findOrFail($initiativeId);}
    public function getUserInitiatives(int $clientId){return ChangeInitiative::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

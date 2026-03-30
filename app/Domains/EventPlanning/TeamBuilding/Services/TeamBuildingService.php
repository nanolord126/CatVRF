<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\TeamBuilding\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TeamBuildingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createEvent(int $facilitatorId,$eventType,$hoursSpent,$dueDate,string $correlationId=""):TeamBuildingEvent{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("team:event:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("team:event:".auth()->id(),3600);
    return DB::transaction(function()use($facilitatorId,$eventType,$hoursSpent,$dueDate,$correlationId){$f=TeamBuildingFacilitator::findOrFail($facilitatorId);$total=(int)($f->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'team','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$e=TeamBuildingEvent::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'facilitator_id'=>$facilitatorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','event_type'=>$eventType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['team'=>true]]);Log::channel('audit')->info('Team building event created',['event_id'=>$e->id,'correlation_id'=>$correlationId]);return $e;});
    }
    public function completeEvent(int $eventId,string $correlationId=""):TeamBuildingEvent{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($eventId,$correlationId){$e=TeamBuildingEvent::findOrFail($eventId);if($e->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$e->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$e->payout_kopecks,'team_payout',['correlation_id'=>$correlationId,'event_id'=>$e->id]);Log::channel('audit')->info('Team building event completed',['event_id'=>$e->id]);return $e;});}
    public function cancelEvent(int $eventId,string $correlationId=""):TeamBuildingEvent{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($eventId,$correlationId){$e=TeamBuildingEvent::findOrFail($eventId);if($e->status==='completed')throw new \RuntimeException("Cannot cancel",400);$e->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($e->payment_status==='completed')$this->wallet->credit(tenant()->id,$e->total_kopecks,'team_refund',['correlation_id'=>$correlationId,'event_id'=>$e->id]);Log::channel('audit')->info('Team building event cancelled',['event_id'=>$e->id]);return $e;});}
    public function getEvent(int $eventId):TeamBuildingEvent{return TeamBuildingEvent::findOrFail($eventId);}
    public function getUserEvents(int $clientId){return TeamBuildingEvent::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

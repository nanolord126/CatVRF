<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventManagementService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createCoordination(int $coordinatorId,$eventType,$coordinationHours,$eventDate,string $correlationId=""):EventCoordination{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("event:coord:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("event:coord:".auth()->id(),3600);
    return DB::transaction(function()use($coordinatorId,$eventType,$coordinationHours,$eventDate,$correlationId){$c=EventCoordinator::findOrFail($coordinatorId);$total=(int)($c->price_kopecks_per_hour*$coordinationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'event_mgmt','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$coord=EventCoordination::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'coordinator_id'=>$coordinatorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','event_type'=>$eventType,'coordination_hours'=>$coordinationHours,'event_date'=>$eventDate,'tags'=>['event'=>true]]);Log::channel('audit')->info('Event coordination created',['coordination_id'=>$coord->id,'correlation_id'=>$correlationId]);return $coord;});
    }
    public function completeCoordination(int $coordinationId,string $correlationId=""):EventCoordination{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($coordinationId,$correlationId){$c=EventCoordination::findOrFail($coordinationId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'event_payout',['correlation_id'=>$correlationId,'coordination_id'=>$c->id]);Log::channel('audit')->info('Event coordination completed',['coordination_id'=>$c->id]);return $c;});}
    public function cancelCoordination(int $coordinationId,string $correlationId=""):EventCoordination{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($coordinationId,$correlationId){$c=EventCoordination::findOrFail($coordinationId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'event_refund',['correlation_id'=>$correlationId,'coordination_id'=>$c->id]);Log::channel('audit')->info('Event coordination cancelled',['coordination_id'=>$c->id]);return $c;});}
    public function getCoordination(int $coordinationId):EventCoordination{return EventCoordination::findOrFail($coordinationId);}
    public function getUserCoordinations(int $clientId){return EventCoordination::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

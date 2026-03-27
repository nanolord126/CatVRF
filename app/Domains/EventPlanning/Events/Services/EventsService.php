<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Events\Services;
use App\Domains\EventPlanning\Events\Models\Event;
use App\Domains\EventPlanning\Events\Models\EventTicket;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * EventsService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventsService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function buyTicket(int $eventId,$quantity,string $correlationId=""):EventTicket{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("event:ticket:".auth()->id(),30))throw new \RuntimeException("Too many",429);RateLimiter::hit("event:ticket:".auth()->id(),3600);
return DB::transaction(function()use($eventId,$quantity,$correlationId){$event=Event::findOrFail($eventId);$total=(int)($event->price_kopecks*$quantity);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'event_ticket','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$t=EventTicket::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'event_id'=>$eventId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.15),'payment_status'=>'pending','ticket_code'=>Str::random(12),'tags'=>['event'=>true,'quantity'=>$quantity]]);Log::channel('audit')->info('Event ticket purchased',['ticket_id'=>$t->id,'correlation_id'=>$correlationId]);return $t;});
}
public function completeTicket(int $ticketId,string $correlationId=""):EventTicket{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($ticketId,$correlationId){$t=EventTicket::findOrFail($ticketId);if($t->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$t->update(['status'=>'confirmed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$t->payout_kopecks,'event_payout',['correlation_id'=>$correlationId,'ticket_id'=>$t->id]);Log::channel('audit')->info('Event ticket confirmed',['ticket_id'=>$t->id]);return $t;});}
public function cancelTicket(int $ticketId,string $correlationId=""):EventTicket{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($ticketId,$correlationId){$t=EventTicket::findOrFail($ticketId);if($t->status==='completed')throw new \RuntimeException("Cannot cancel",400);$t->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($t->payment_status==='completed')$this->wallet->credit(tenant()->id,$t->total_kopecks,'event_refund',['correlation_id'=>$correlationId,'ticket_id'=>$t->id]);Log::channel('audit')->info('Event ticket cancelled',['ticket_id'=>$t->id]);return $t;});}
public function getTicket(int $ticketId):EventTicket{return EventTicket::findOrFail($ticketId);}
public function getUserTickets(int $clientId){return EventTicket::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

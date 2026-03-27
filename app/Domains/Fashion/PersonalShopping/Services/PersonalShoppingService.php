<?php

declare(strict_types=1);

namespace App\Domains\Fashion\PersonalShopping\Services;
use App\Domains\Fashion\PersonalShopping\Models\PersonalShopper;
use App\Domains\Fashion\PersonalShopping\Models\ShoppingSession;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * PersonalShoppingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PersonalShoppingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createSession(int $shopperId,$sessionDate,$durationHours,$itemsPurchased,string $correlationId=""):ShoppingSession{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("pshopping:session:".auth()->id(),12))throw new \RuntimeException("Too many",429);RateLimiter::hit("pshopping:session:".auth()->id(),3600);
return DB::transaction(function()use($shopperId,$sessionDate,$durationHours,$itemsPurchased,$correlationId){$s=PersonalShopper::findOrFail($shopperId);$total=(int)($s->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'personal_shopping','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$ss=ShoppingSession->create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'shopper_id'=>$shopperId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','session_date'=>$sessionDate,'duration_hours'=>$durationHours,'items_purchased'=>$itemsPurchased,'tags'=>['personal_shopping'=>true]]);Log::channel('audit')->info('Personal shopping session created',['session_id'=>$ss->id,'correlation_id'=>$correlationId]);return $ss;});
}
public function completeSession(int $sessionId,string $correlationId=""):ShoppingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ShoppingSession->findOrFail($sessionId);if($s->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$s->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$s->payout_kopecks,'pshopping_payout',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Personal shopping session completed',['session_id'=>$s->id]);return $s;});}
public function cancelSession(int $sessionId,string $correlationId=""):ShoppingSession{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($sessionId,$correlationId){$s=ShoppingSession->findOrFail($sessionId);if($s->status==='completed')throw new \RuntimeException("Cannot cancel",400);$s->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($s->payment_status==='completed')$this->wallet->credit(tenant()->id,$s->total_kopecks,'pshopping_refund',['correlation_id'=>$correlationId,'session_id'=>$s->id]);Log::channel('audit')->info('Personal shopping session cancelled',['session_id'=>$s->id]);return $s;});}
public function getSession(int $sessionId):ShoppingSession{return ShoppingSession->findOrFail($sessionId);}
public function getUserSessions(int $clientId){return ShoppingSession->where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

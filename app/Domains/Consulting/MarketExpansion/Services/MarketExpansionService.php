<?php

declare(strict_types=1);

namespace App\Domains\Consulting\MarketExpansion\Services;
use App\Domains\Consulting\MarketExpansion\Models\ExpansionStrategist;
use App\Domains\Consulting\MarketExpansion\Models\ExpansionStrategy;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * MarketExpansionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MarketExpansionService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createStrategy(int $strategistId,$strategyType,$hoursSpent,$dueDate,string $correlationId=""):ExpansionStrategy{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("expand:strat:".auth()->id(),14))throw new \RuntimeException("Too many",429);RateLimiter::hit("expand:strat:".auth()->id(),3600);
return DB::transaction(function()use($strategistId,$strategyType,$hoursSpent,$dueDate,$correlationId){$s=ExpansionStrategist::findOrFail($strategistId);$total=(int)($s->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'expand','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$st=ExpansionStrategy::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'strategist_id'=>$strategistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','strategy_type'=>$strategyType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['expand'=>true]]);Log::channel('audit')->info('Expansion strategy created',['strategy_id'=>$st->id,'correlation_id'=>$correlationId]);return $st;});
}
public function completeStrategy(int $strategyId,string $correlationId=""):ExpansionStrategy{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($strategyId,$correlationId){$st=ExpansionStrategy::findOrFail($strategyId);if($st->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$st->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$st->payout_kopecks,'expand_payout',['correlation_id'=>$correlationId,'strategy_id'=>$st->id]);Log::channel('audit')->info('Expansion strategy completed',['strategy_id'=>$st->id]);return $st;});}
public function cancelStrategy(int $strategyId,string $correlationId=""):ExpansionStrategy{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($strategyId,$correlationId){$st=ExpansionStrategy::findOrFail($strategyId);if($st->status==='completed')throw new \RuntimeException("Cannot cancel",400);$st->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($st->payment_status==='completed')$this->wallet->credit(tenant()->id,$st->total_kopecks,'expand_refund',['correlation_id'=>$correlationId,'strategy_id'=>$st->id]);Log::channel('audit')->info('Expansion strategy cancelled',['strategy_id'=>$st->id]);return $st;});}
public function getStrategy(int $strategyId):ExpansionStrategy{return ExpansionStrategy::findOrFail($strategyId);}
public function getUserStrategies(int $clientId){return ExpansionStrategy::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

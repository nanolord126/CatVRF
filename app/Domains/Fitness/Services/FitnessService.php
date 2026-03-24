<?php declare(strict_types=1);
namespace App\Domains\Fitness\Services;
use App\Domains\Fitness\Models\GymClub;
use App\Domains\Fitness\Models\FitnessMembership;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class FitnessService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createMembership(int $gymId,$membershipType,$monthCount,string $correlationId=""):FitnessMembership{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("fitness:member:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("fitness:member:".auth()->id(),3600);
return DB::transaction(function()use($gymId,$membershipType,$monthCount,$correlationId){$g=GymClub::findOrFail($gymId);$total=(int)($g->price_kopecks_per_month*$monthCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'fitness_member','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$m=FitnessMembership::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'gym_id'=>$gymId,'member_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','membership_type'=>$membershipType,'start_date'=>now(),'end_date'=>now()->addMonths($monthCount),'tags'=>['fitness'=>true]]);Log::channel('audit')->info('Fitness membership created',['membership_id'=>$m->id,'correlation_id'=>$correlationId]);return $m;});
}
public function completeMembership(int $membershipId,string $correlationId=""):FitnessMembership{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($membershipId,$correlationId){$m=FitnessMembership::findOrFail($membershipId);if($m->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$m->update(['status'=>'active','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$m->payout_kopecks,'fitness_payout',['correlation_id'=>$correlationId,'membership_id'=>$m->id]);Log::channel('audit')->info('Fitness membership activated',['membership_id'=>$m->id]);return $m;});}
public function cancelMembership(int $membershipId,string $correlationId=""):FitnessMembership{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($membershipId,$correlationId){$m=FitnessMembership::findOrFail($membershipId);if($m->status==='active')throw new \RuntimeException("Cannot cancel",400);$m->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($m->payment_status==='completed')$this->wallet->credit(tenant()->id,$m->total_kopecks,'fitness_refund',['correlation_id'=>$correlationId,'membership_id'=>$m->id]);Log::channel('audit')->info('Fitness membership cancelled',['membership_id'=>$m->id]);return $m;});}
public function getMembership(int $membershipId):FitnessMembership{return FitnessMembership::findOrFail($membershipId);}
public function getUserMemberships(int $memberId){return FitnessMembership::where('member_id',$memberId)->orderBy('created_at','desc')->take(10)->get();}
}

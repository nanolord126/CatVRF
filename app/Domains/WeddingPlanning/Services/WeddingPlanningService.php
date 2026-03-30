<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WeddingPlanningService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $plannerId,$weddingDate,$guestCount,$venueType,string $correlationId=""):WeddingProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("wedding:plan:".auth()->id(),3))throw new \RuntimeException("Too many",429);RateLimiter::hit("wedding:plan:".auth()->id(),3600);
    return DB::transaction(function()use($plannerId,$weddingDate,$guestCount,$venueType,$correlationId){$p=WeddingPlanner::findOrFail($plannerId);$total=$p->base_price_kopecks+($p->price_per_guest*$guestCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'wedding_plan','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$w=WeddingProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'planner_id'=>$plannerId,'couple_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','wedding_date'=>$weddingDate,'guest_count'=>$guestCount,'venue_type'=>$venueType,'tags'=>['wedding'=>true]]);Log::channel('audit')->info('Wedding project created',['project_id'=>$w->id,'correlation_id'=>$correlationId]);return $w;});
    }
    public function completeProject(int $projectId,string $correlationId=""):WeddingProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$w=WeddingProject::findOrFail($projectId);if($w->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$w->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$w->payout_kopecks,'wedding_payout',['correlation_id'=>$correlationId,'project_id'=>$w->id]);Log::channel('audit')->info('Wedding project completed',['project_id'=>$w->id]);return $w;});}
    public function cancelProject(int $projectId,string $correlationId=""):WeddingProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$w=WeddingProject::findOrFail($projectId);if($w->status==='completed')throw new \RuntimeException("Cannot cancel",400);$w->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($w->payment_status==='completed')$this->wallet->credit(tenant()->id,$w->total_kopecks,'wedding_refund',['correlation_id'=>$correlationId,'project_id'=>$w->id]);Log::channel('audit')->info('Wedding project cancelled',['project_id'=>$w->id]);return $w;});}
    public function getProject(int $projectId):WeddingProject{return WeddingProject::findOrFail($projectId);}
    public function getUserProjects(int $coupleId){return WeddingProject::where('couple_id',$coupleId)->orderBy('created_at','desc')->take(10)->get();}
}

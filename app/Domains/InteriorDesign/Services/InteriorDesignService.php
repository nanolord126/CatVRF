<?php declare(strict_types=1);
namespace App\Domains\InteriorDesign\Services;
use App\Domains\InteriorDesign\Models\InteriorDesigner;
use App\Domains\InteriorDesign\Models\DesignProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class InteriorDesignService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $designerId,$style,$spaceSqm,$dueDate,string $correlationId=""):DesignProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("interior:project:".auth()->id(),4))throw new \RuntimeException("Too many",429);RateLimiter::hit("interior:project:".auth()->id(),3600);
return DB::transaction(function()use($designerId,$style,$spaceSqm,$dueDate,$correlationId){$d=InteriorDesigner::findOrFail($designerId);$total=(int)($d->price_kopecks_per_sqm*$spaceSqm);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'interior_design','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=DesignProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'designer_id'=>$designerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','style'=>$style,'space_sqm'=>$spaceSqm,'due_date'=>$dueDate,'tags'=>['interior'=>true]]);Log::channel('audit')->info('Interior design project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):DesignProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=DesignProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'interior_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Interior design project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):DesignProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=DesignProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'interior_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Interior design project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):DesignProject{return DesignProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return DesignProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

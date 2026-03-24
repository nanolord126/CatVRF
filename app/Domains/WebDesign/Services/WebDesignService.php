<?php declare(strict_types=1);
namespace App\Domains\WebDesign\Services;
use App\Domains\WebDesign\Models\WebDesigner;
use App\Domains\WebDesign\Models\WebsiteProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class WebDesignService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createProject(int $designerId,$projectType,$pagesCount,$dueDate,string $correlationId=""):WebsiteProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("webdesign:project:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("webdesign:project:".auth()->id(),3600);
return DB::transaction(function()use($designerId,$projectType,$pagesCount,$dueDate,$correlationId){$d=WebDesigner::findOrFail($designerId);$total=$d->base_price_kopecks+(50000*$pagesCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'web_design','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=WebsiteProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'designer_id'=>$designerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'pages_count'=>$pagesCount,'due_date'=>$dueDate,'tags'=>['webdesign'=>true]]);Log::channel('audit')->info('Website project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):WebsiteProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=WebsiteProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'webdesign_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Website project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):WebsiteProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=WebsiteProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'webdesign_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Website project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):WebsiteProject{return WebsiteProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return WebsiteProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

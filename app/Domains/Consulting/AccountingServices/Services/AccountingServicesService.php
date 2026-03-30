<?php declare(strict_types=1);

namespace App\Domains\Consulting\AccountingServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AccountingServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $firmId,$projectType,$hoursAllocated,$dueDate,string $correlationId=""):AccountingProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("acct:proj:".auth()->id(),8))throw new \RuntimeException("Too many",429);RateLimiter::hit("acct:proj:".auth()->id(),3600);
    return DB::transaction(function()use($firmId,$projectType,$hoursAllocated,$dueDate,$correlationId){$f=AccountingFirm::findOrFail($firmId);$total=(int)($f->price_kopecks_per_hour*$hoursAllocated);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'accounting','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=AccountingProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'firm_id'=>$firmId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_allocated'=>$hoursAllocated,'due_date'=>$dueDate,'tags'=>['accounting'=>true]]);Log::channel('audit')->info('Accounting project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):AccountingProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=AccountingProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'acct_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Accounting project completed',['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):AccountingProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=AccountingProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'acct_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Accounting project cancelled',['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):AccountingProject{return AccountingProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return AccountingProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

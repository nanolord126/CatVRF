<?php declare(strict_types=1);

namespace App\Domains\Consulting\OperationsManagement\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OperationsManagementService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $managerId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):OperationsProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("ops:proj:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("ops:proj:".auth()->id(),3600);
    return DB::transaction(function()use($managerId,$projectType,$hoursSpent,$dueDate,$correlationId){$m=OperationsManager::findOrFail($managerId);$total=(int)($m->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'operations','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=OperationsProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'manager_id'=>$managerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['operations'=>true]]);Log::channel('audit')->info('Operations project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):OperationsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=OperationsProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'ops_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Operations project completed',['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):OperationsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=OperationsProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'ops_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Operations project cancelled',['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):OperationsProject{return OperationsProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return OperationsProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

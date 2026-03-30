<?php declare(strict_types=1);

namespace App\Domains\Consulting\RoboticsSystems\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RoboticsSystemsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $engineerId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):RoboticsProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("rob:proj:".auth()->id(),25))throw new \RuntimeException("Too many",429);RateLimiter::hit("rob:proj:".auth()->id(),3600);
    return DB::transaction(function()use($engineerId,$projectType,$hoursSpent,$dueDate,$correlationId){$e=RoboticsEngineer::findOrFail($engineerId);$total=(int)($e->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'rob','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=RoboticsProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'engineer_id'=>$engineerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['rob'=>true]]);Log::channel('audit')->info('Robotics project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):RoboticsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=RoboticsProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'rob_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Robotics project completed',['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):RoboticsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=RoboticsProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'rob_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Robotics project cancelled',['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):RoboticsProject{return RoboticsProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return RoboticsProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

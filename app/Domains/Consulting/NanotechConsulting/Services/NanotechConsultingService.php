<?php declare(strict_types=1);

namespace App\Domains\Consulting\NanotechConsulting\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NanotechConsultingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $scientistId,$projectType,$hoursSpent,$dueDate,string $correlationId=""):NanotechProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("nano:proj:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("nano:proj:".auth()->id(),3600);
    return DB::transaction(function()use($scientistId,$projectType,$hoursSpent,$dueDate,$correlationId){$s=NanotechScientist::findOrFail($scientistId);$total=(int)($s->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'nano','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=NanotechProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'scientist_id'=>$scientistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['nano'=>true]]);Log::channel('audit')->info('Nanotech project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):NanotechProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=NanotechProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'nano_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Nanotech project completed',['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):NanotechProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=NanotechProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'nano_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Nanotech project cancelled',['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):NanotechProject{return NanotechProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return NanotechProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

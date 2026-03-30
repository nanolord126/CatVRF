<?php declare(strict_types=1);

namespace App\Domains\Consulting\CloudArchitecture\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CloudArchitectureService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $architectId,$projectType,$architectureHours,$dueDate,string $correlationId=""):CloudProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("cloud:proj:".auth()->id(),6))throw new \RuntimeException("Too many",429);RateLimiter::hit("cloud:proj:".auth()->id(),3600);
    return DB::transaction(function()use($architectId,$projectType,$architectureHours,$dueDate,$correlationId){$a=CloudArchitect::findOrFail($architectId);$total=(int)($a->price_kopecks_per_hour*$architectureHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'cloud_arch','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=CloudProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'architect_id'=>$architectId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'architecture_hours'=>$architectureHours,'due_date'=>$dueDate,'tags'=>['cloud'=>true]]);Log::channel('audit')->info('Cloud project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):CloudProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=CloudProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'cloud_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Cloud project completed',['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):CloudProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=CloudProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'cloud_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Cloud project cancelled',['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):CloudProject{return CloudProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return CloudProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

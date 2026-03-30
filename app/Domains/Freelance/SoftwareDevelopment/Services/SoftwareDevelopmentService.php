<?php declare(strict_types=1);

namespace App\Domains\Freelance\SoftwareDevelopment\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SoftwareDevelopmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $developerId,$projectType,$developmentHours,$dueDate,string $correlationId=""):SoftwareProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("softdev:project:".auth()->id(),7))throw new \RuntimeException("Too many",429);RateLimiter::hit("softdev:project:".auth()->id(),3600);
    return DB::transaction(function()use($developerId,$projectType,$developmentHours,$dueDate,$correlationId){$d=Developer::findOrFail($developerId);$total=(int)($d->price_kopecks_per_hour*$developmentHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'software_dev','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=SoftwareProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'developer_id'=>$developerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'development_hours'=>$developmentHours,'due_date'=>$dueDate,'tags'=>['software'=>true]]);Log::channel('audit')->info('Software project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):SoftwareProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=SoftwareProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'software_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Software project completed',['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):SoftwareProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=SoftwareProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'software_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Software project cancelled',['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):SoftwareProject{return SoftwareProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return SoftwareProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

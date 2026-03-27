<?php

declare(strict_types=1);

namespace App\Domains\Art\VideoEditing\Services;
use App\Domains\Art\VideoEditing\Models\VideoEditor;
use App\Domains\Art\VideoEditing\Models\VideoProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * VideoEditingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class VideoEditingService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $editorId,$projectType,$editingHours,$dueDate,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("video:proj:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("video:proj:".auth()->id(),3600);
return DB::transaction(function()use($editorId,$projectType,$editingHours,$dueDate,$correlationId){$e=VideoEditor::findOrFail($editorId);$total=(int)($e->price_kopecks_per_hour*$editingHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'video_editing','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=VideoProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'editor_id'=>$editorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'editing_hours'=>$editingHours,'due_date'=>$dueDate,'tags'=>['video'=>true]]);Log::channel('audit')->info('Video project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=VideoProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'video_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Video project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):VideoProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=VideoProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'video_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Video project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):VideoProject{return VideoProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return VideoProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

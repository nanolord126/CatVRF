<?php

declare(strict_types=1);

namespace App\Domains\Art\PodcastProduction\Services;
use App\Domains\Art\PodcastProduction\Models\PodcastProducer;
use App\Domains\Art\PodcastProduction\Models\PodcastProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * PodcastProductionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PodcastProductionService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $producerId,$projectType,$productionHours,$dueDate,string $correlationId=""):PodcastProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("podcast:proj:".auth()->id(),7))throw new \RuntimeException("Too many",429);RateLimiter::hit("podcast:proj:".auth()->id(),3600);
return DB::transaction(function()use($producerId,$projectType,$productionHours,$dueDate,$correlationId){$p=PodcastProducer::findOrFail($producerId);$total=(int)($p->price_kopecks_per_hour*$productionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'podcast_prod','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$proj=PodcastProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'producer_id'=>$producerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'production_hours'=>$productionHours,'due_date'=>$dueDate,'tags'=>['podcast'=>true]]);Log::channel('audit')->info('Podcast project created',['project_id'=>$proj->id,'correlation_id'=>$correlationId]);return $proj;});
}
public function completeProject(int $projectId,string $correlationId=""):PodcastProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=PodcastProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'podcast_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Podcast project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):PodcastProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=PodcastProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'podcast_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Podcast project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):PodcastProject{return PodcastProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return PodcastProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php

declare(strict_types=1);

namespace App\Domains\Art\ArtisticServices\Services;
use App\Domains\Art\ArtisticServices\Models\Artist;
use App\Domains\Art\ArtisticServices\Models\ArtisticProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * ArtisticServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ArtisticServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createProject(int $artistId,$projectType,$artisticHours,$dueDate,string $correlationId=""):ArtisticProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("art:proj:".auth()->id(),19))throw new \RuntimeException("Too many",429);RateLimiter::hit("art:proj:".auth()->id(),3600);
return DB::transaction(function()use($artistId,$projectType,$artisticHours,$dueDate,$correlationId){$a=Artist::findOrFail($artistId);$total=(int)($a->price_kopecks_per_hour*$artisticHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'artistic','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=ArtisticProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'artist_id'=>$artistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'artistic_hours'=>$artisticHours,'due_date'=>$dueDate,'tags'=>['artistic'=>true]]);Log::channel('audit')->info('Artistic project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
}
public function completeProject(int $projectId,string $correlationId=""):ArtisticProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=ArtisticProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'art_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Artistic project completed',['project_id'=>$p->id]);return $p;});}
public function cancelProject(int $projectId,string $correlationId=""):ArtisticProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=ArtisticProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'art_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Artistic project cancelled',['project_id'=>$p->id]);return $p;});}
public function getProject(int $projectId):ArtisticProject{return ArtisticProject::findOrFail($projectId);}
public function getUserProjects(int $clientId){return ArtisticProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicProduction\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicProductionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $producerId,$projectType,$productionHours,$dueDate,string $correlationId=""):MusicProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("music:project:".auth()->id(),5))throw new \RuntimeException("Too many",429);RateLimiter::hit("music:project:".auth()->id(),3600);
    return DB::transaction(function()use($producerId,$projectType,$productionHours,$dueDate,$correlationId){$p=MusicProducer::findOrFail($producerId);$total=(int)($p->price_kopecks_per_hour*$productionHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'music_prod','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$m=MusicProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'producer_id'=>$producerId,'artist_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'production_hours'=>$productionHours,'due_date'=>$dueDate,'tags'=>['music'=>true]]);Log::channel('audit')->info('Music project created',['project_id'=>$m->id,'correlation_id'=>$correlationId]);return $m;});
    }
    public function completeProject(int $projectId,string $correlationId=""):MusicProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$m=MusicProject::findOrFail($projectId);if($m->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$m->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$m->payout_kopecks,'music_payout',['correlation_id'=>$correlationId,'project_id'=>$m->id]);Log::channel('audit')->info('Music project completed',['project_id'=>$m->id]);return $m;});}
    public function cancelProject(int $projectId,string $correlationId=""):MusicProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$m=MusicProject::findOrFail($projectId);if($m->status==='completed')throw new \RuntimeException("Cannot cancel",400);$m->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($m->payment_status==='completed')$this->wallet->credit(tenant()->id,$m->total_kopecks,'music_refund',['correlation_id'=>$correlationId,'project_id'=>$m->id]);Log::channel('audit')->info('Music project cancelled',['project_id'=>$m->id]);return $m;});}
    public function getProject(int $projectId):MusicProject{return MusicProject::findOrFail($projectId);}
    public function getUserProjects(int $artistId){return MusicProject::where('artist_id',$artistId)->orderBy('created_at','desc')->take(10)->get();}
}

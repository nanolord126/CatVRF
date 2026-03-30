<?php declare(strict_types=1);

namespace App\Domains\Consulting\DataAnalytics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DataAnalyticsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProject(int $analystId,$projectType,$analysisHours,$dueDate,string $correlationId=""):AnalyticsProject{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("data:proj:".auth()->id(),14))throw new \RuntimeException("Too many",429);RateLimiter::hit("data:proj:".auth()->id(),3600);
    return DB::transaction(function()use($analystId,$projectType,$analysisHours,$dueDate,$correlationId){$a=DataAnalyst::findOrFail($analystId);$total=(int)($a->price_kopecks_per_hour*$analysisHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'data_analytics','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=AnalyticsProject::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'analyst_id'=>$analystId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','project_type'=>$projectType,'analysis_hours'=>$analysisHours,'due_date'=>$dueDate,'tags'=>['analytics'=>true]]);Log::channel('audit')->info('Analytics project created',['project_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProject(int $projectId,string $correlationId=""):AnalyticsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=AnalyticsProject::findOrFail($projectId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'analytics_payout',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Analytics project completed',['project_id'=>$p->id]);return $p;});}
    public function cancelProject(int $projectId,string $correlationId=""):AnalyticsProject{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($projectId,$correlationId){$p=AnalyticsProject::findOrFail($projectId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'analytics_refund',['correlation_id'=>$correlationId,'project_id'=>$p->id]);Log::channel('audit')->info('Analytics project cancelled',['project_id'=>$p->id]);return $p;});}
    public function getProject(int $projectId):AnalyticsProject{return AnalyticsProject::findOrFail($projectId);}
    public function getUserProjects(int $clientId){return AnalyticsProject::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

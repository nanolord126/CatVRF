<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\LeadershipDevelopment\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LeadershipDevelopmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProgram(int $mentorId,$programType,$hoursSpent,$dueDate,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("leader:prog:".auth()->id(),11))throw new \RuntimeException("Too many",429);RateLimiter::hit("leader:prog:".auth()->id(),3600);
    return DB::transaction(function()use($mentorId,$programType,$hoursSpent,$dueDate,$correlationId){$m=LeadershipMentor::findOrFail($mentorId);$total=(int)($m->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'leader','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=MentorshipProgram::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'mentor_id'=>$mentorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','program_type'=>$programType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['leader'=>true]]);Log::channel('audit')->info('Mentorship program created',['program_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProgram(int $programId,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($programId,$correlationId){$p=MentorshipProgram::findOrFail($programId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'leader_payout',['correlation_id'=>$correlationId,'program_id'=>$p->id]);Log::channel('audit')->info('Mentorship program completed',['program_id'=>$p->id]);return $p;});}
    public function cancelProgram(int $programId,string $correlationId=""):MentorshipProgram{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($programId,$correlationId){$p=MentorshipProgram::findOrFail($programId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'leader_refund',['correlation_id'=>$correlationId,'program_id'=>$p->id]);Log::channel('audit')->info('Mentorship program cancelled',['program_id'=>$p->id]);return $p;});}
    public function getProgram(int $programId):MentorshipProgram{return MentorshipProgram::findOrFail($programId);}
    public function getUserPrograms(int $clientId){return MentorshipProgram::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

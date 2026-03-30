<?php declare(strict_types=1);

namespace App\Domains\Consulting\CustomerRetention\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CustomerRetentionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createProgram(int $specialistId,$programType,$hoursSpent,$dueDate,string $correlationId=""):RetentionProgram{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("ret:prog:".auth()->id(),13))throw new \RuntimeException("Too many",429);RateLimiter::hit("ret:prog:".auth()->id(),3600);
    return DB::transaction(function()use($specialistId,$programType,$hoursSpent,$dueDate,$correlationId){$s=RetentionSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_hour*$hoursSpent);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'retention','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$p=RetentionProgram::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','program_type'=>$programType,'hours_spent'=>$hoursSpent,'due_date'=>$dueDate,'tags'=>['retention'=>true]]);Log::channel('audit')->info('Retention program created',['program_id'=>$p->id,'correlation_id'=>$correlationId]);return $p;});
    }
    public function completeProgram(int $programId,string $correlationId=""):RetentionProgram{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($programId,$correlationId){$p=RetentionProgram::findOrFail($programId);if($p->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$p->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$p->payout_kopecks,'retention_payout',['correlation_id'=>$correlationId,'program_id'=>$p->id]);Log::channel('audit')->info('Retention program completed',['program_id'=>$p->id]);return $p;});}
    public function cancelProgram(int $programId,string $correlationId=""):RetentionProgram{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($programId,$correlationId){$p=RetentionProgram::findOrFail($programId);if($p->status==='completed')throw new \RuntimeException("Cannot cancel",400);$p->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($p->payment_status==='completed')$this->wallet->credit(tenant()->id,$p->total_kopecks,'retention_refund',['correlation_id'=>$correlationId,'program_id'=>$p->id]);Log::channel('audit')->info('Retention program cancelled',['program_id'=>$p->id]);return $p;});}
    public function getProgram(int $programId):RetentionProgram{return RetentionProgram::findOrFail($programId);}
    public function getUserPrograms(int $clientId){return RetentionProgram::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

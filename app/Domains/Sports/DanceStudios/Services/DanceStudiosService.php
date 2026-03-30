<?php declare(strict_types=1);

namespace App\Domains\Sports\DanceStudios\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DanceStudiosService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createClass(int $studioId,$classDate,string $correlationId=""):DanceClass{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("dance:class:".auth()->id(),30))throw new \RuntimeException("Too many",429);RateLimiter::hit("dance:class:".auth()->id(),3600);
    return DB::transaction(function()use($studioId,$classDate,$correlationId){$s=DanceStudio::findOrFail($studioId);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'dance_class','correlation_id'=>$correlationId,'amount'=>$s->price_kopecks_per_class]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$c=DanceClass::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'studio_id'=>$studioId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$s->price_kopecks_per_class,'payout_kopecks'=>$s->price_kopecks_per_class-(int)($s->price_kopecks_per_class*0.14),'payment_status'=>'pending','class_date'=>$classDate,'tags'=>['dance'=>true]]);Log::channel('audit')->info('Dance class booked',['class_id'=>$c->id,'correlation_id'=>$correlationId]);return $c;});
    }
    public function completeClass(int $classId,string $correlationId=""):DanceClass{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($classId,$correlationId){$c=DanceClass::findOrFail($classId);if($c->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$c->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$c->payout_kopecks,'dance_payout',['correlation_id'=>$correlationId,'class_id'=>$c->id]);Log::channel('audit')->info('Dance class completed',['class_id'=>$c->id]);return $c;});}
    public function cancelClass(int $classId,string $correlationId=""):DanceClass{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($classId,$correlationId){$c=DanceClass::findOrFail($classId);if($c->status==='completed')throw new \RuntimeException("Cannot cancel",400);$c->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($c->payment_status==='completed')$this->wallet->credit(tenant()->id,$c->total_kopecks,'dance_refund',['correlation_id'=>$correlationId,'class_id'=>$c->id]);Log::channel('audit')->info('Dance class cancelled',['class_id'=>$c->id]);return $c;});}
    public function getClass(int $classId):DanceClass{return DanceClass::findOrFail($classId);}
    public function getUserClasses(int $studentId){return DanceClass::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

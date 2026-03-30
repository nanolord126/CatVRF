<?php declare(strict_types=1);

namespace App\Domains\Education\DrivingSchools\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DrivingSchoolsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createLesson(int $schoolId,$lessonDate,$durationHours,$category,string $correlationId=""):DrivingLesson{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("driving:lesson:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("driving:lesson:".auth()->id(),3600);
    return DB::transaction(function()use($schoolId,$lessonDate,$durationHours,$category,$correlationId){$s=DrivingSchool::findOrFail($schoolId);$total=(int)($s->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'driving_lesson','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$l=DrivingLesson::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'school_id'=>$schoolId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','lesson_date'=>$lessonDate,'duration_hours'=>$durationHours,'category'=>$category,'tags'=>['driving'=>true]]);Log::channel('audit')->info('Driving lesson created',['lesson_id'=>$l->id,'correlation_id'=>$correlationId]);return $l;});
    }
    public function completeLesson(int $lessonId,string $correlationId=""):DrivingLesson{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($lessonId,$correlationId){$l=DrivingLesson::findOrFail($lessonId);if($l->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$l->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$l->payout_kopecks,'driving_payout',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);Log::channel('audit')->info('Driving lesson completed',['lesson_id'=>$l->id]);return $l;});}
    public function cancelLesson(int $lessonId,string $correlationId=""):DrivingLesson{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($lessonId,$correlationId){$l=DrivingLesson::findOrFail($lessonId);if($l->status==='completed')throw new \RuntimeException("Cannot cancel",400);$l->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($l->payment_status==='completed')$this->wallet->credit(tenant()->id,$l->total_kopecks,'driving_refund',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);Log::channel('audit')->info('Driving lesson cancelled',['lesson_id'=>$l->id]);return $l;});}
    public function getLesson(int $lessonId):DrivingLesson{return DrivingLesson::findOrFail($lessonId);}
    public function getUserLessons(int $studentId){return DrivingLesson::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

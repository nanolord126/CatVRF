<?php declare(strict_types=1);

namespace App\Domains\Sports\DanceInstructor\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DanceInstructorService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
    public function createLesson(int $teacherId,$danceStyle,$lessonHours,$lessonDate,string $correlationId=""):DanceLesson{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("dance:lesson:".auth()->id(),23))throw new \RuntimeException("Too many",429);RateLimiter::hit("dance:lesson:".auth()->id(),3600);
    return DB::transaction(function()use($teacherId,$danceStyle,$lessonHours,$lessonDate,$correlationId){$t=DanceTeacher::findOrFail($teacherId);$total=(int)($t->price_kopecks_per_hour*$lessonHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'dance_lesson','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$l=DanceLesson::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'teacher_id'=>$teacherId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','dance_style'=>$danceStyle,'lesson_hours'=>$lessonHours,'lesson_date'=>$lessonDate,'tags'=>['dance'=>true]]);Log::channel('audit')->info('Dance lesson created',['lesson_id'=>$l->id,'correlation_id'=>$correlationId]);return $l;});
    }
    public function completeLesson(int $lessonId,string $correlationId=""):DanceLesson{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($lessonId,$correlationId){$l=DanceLesson::findOrFail($lessonId);if($l->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$l->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$l->payout_kopecks,'dance_payout',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);Log::channel('audit')->info('Dance lesson completed',['lesson_id'=>$l->id]);return $l;});}
    public function cancelLesson(int $lessonId,string $correlationId=""):DanceLesson{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($lessonId,$correlationId){$l=DanceLesson::findOrFail($lessonId);if($l->status==='completed')throw new \RuntimeException("Cannot cancel",400);$l->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($l->payment_status==='completed')$this->wallet->credit(tenant()->id,$l->total_kopecks,'dance_refund',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);Log::channel('audit')->info('Dance lesson cancelled',['lesson_id'=>$l->id]);return $l;});}
    public function getLesson(int $lessonId):DanceLesson{return DanceLesson::findOrFail($lessonId);}
    public function getUserLessons(int $studentId){return DanceLesson::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

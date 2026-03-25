declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\DrivingSchools\Services;
use App\Domains\DrivingSchools\Models\DrivingSchool;
use App\Domains\DrivingSchools\Models\DrivingLesson;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * DrivingSchoolsService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DrivingSchoolsService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createLesson(int $schoolId,$lessonDate,$durationHours,$category,string $correlationId=""):DrivingLesson{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("driving:lesson:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("driving:lesson:".auth()->id(),3600);
return $this->db->transaction(function()use($schoolId,$lessonDate,$durationHours,$category,$correlationId){$s=DrivingSchool::findOrFail($schoolId);$total=(int)($s->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'driving_lesson','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$l=DrivingLesson::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'school_id'=>$schoolId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','lesson_date'=>$lessonDate,'duration_hours'=>$durationHours,'category'=>$category,'tags'=>['driving'=>true]]);$this->log->channel('audit')->info('Driving lesson created',['lesson_id'=>$l->id,'correlation_id'=>$correlationId]);return $l;});
}
public function completeLesson(int $lessonId,string $correlationId=""):DrivingLesson{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($lessonId,$correlationId){$l=DrivingLesson::findOrFail($lessonId);if($l->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$l->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$l->payout_kopecks,'driving_payout',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);$this->log->channel('audit')->info('Driving lesson completed',['lesson_id'=>$l->id]);return $l;});}
public function cancelLesson(int $lessonId,string $correlationId=""):DrivingLesson{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($lessonId,$correlationId){$l=DrivingLesson::findOrFail($lessonId);if($l->status==='completed')throw new \RuntimeException("Cannot cancel",400);$l->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($l->payment_status==='completed')$this->wallet->credit(tenant()->id,$l->total_kopecks,'driving_refund',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);$this->log->channel('audit')->info('Driving lesson cancelled',['lesson_id'=>$l->id]);return $l;});}
public function getLesson(int $lessonId):DrivingLesson{return DrivingLesson::findOrFail($lessonId);}
public function getUserLessons(int $studentId){return DrivingLesson::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

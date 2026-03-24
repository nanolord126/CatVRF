<?php declare(strict_types=1);
namespace App\Domains\MusicalInstruments\Services;
use App\Domains\MusicalInstruments\Models\InstrumentTeacher;
use App\Domains\MusicalInstruments\Models\InstrumentLesson;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class MusicalInstrumentsService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createLesson(int $teacherId,$instrument,$lessonHours,$lessonDate,string $correlationId=""):InstrumentLesson{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("instr:lesson:".auth()->id(),16))throw new \RuntimeException("Too many",429);RateLimiter::hit("instr:lesson:".auth()->id(),3600);
return DB::transaction(function()use($teacherId,$instrument,$lessonHours,$lessonDate,$correlationId){$t=InstrumentTeacher::findOrFail($teacherId);$total=(int)($t->price_kopecks_per_hour*$lessonHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'instrument_lesson','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$l=InstrumentLesson::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'teacher_id'=>$teacherId,'student_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','instrument'=>$instrument,'lesson_hours'=>$lessonHours,'lesson_date'=>$lessonDate,'tags'=>['instrument'=>true]]);Log::channel('audit')->info('Instrument lesson created',['lesson_id'=>$l->id,'correlation_id'=>$correlationId]);return $l;});
}
public function completeLesson(int $lessonId,string $correlationId=""):InstrumentLesson{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($lessonId,$correlationId){$l=InstrumentLesson::findOrFail($lessonId);if($l->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$l->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$l->payout_kopecks,'instr_payout',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);Log::channel('audit')->info('Instrument lesson completed',['lesson_id'=>$l->id]);return $l;});}
public function cancelLesson(int $lessonId,string $correlationId=""):InstrumentLesson{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($lessonId,$correlationId){$l=InstrumentLesson::findOrFail($lessonId);if($l->status==='completed')throw new \RuntimeException("Cannot cancel",400);$l->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($l->payment_status==='completed')$this->wallet->credit(tenant()->id,$l->total_kopecks,'instr_refund',['correlation_id'=>$correlationId,'lesson_id'=>$l->id]);Log::channel('audit')->info('Instrument lesson cancelled',['lesson_id'=>$l->id]);return $l;});}
public function getLesson(int $lessonId):InstrumentLesson{return InstrumentLesson::findOrFail($lessonId);}
public function getUserLessons(int $studentId){return InstrumentLesson::where('student_id',$studentId)->orderBy('created_at','desc')->take(10)->get();}
}

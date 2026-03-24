<?php declare(strict_types=1);
namespace App\Domains\Dentistry\Services;
use App\Domains\Dentistry\Models\Dentist;
use App\Domains\Dentistry\Models\DentalAppointment;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class DentistryService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createAppointment(int $dentistId,$appointmentDate,$durationMinutes,$serviceType,string $correlationId=""):DentalAppointment{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("dental:appt:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("dental:appt:".auth()->id(),3600);
return DB::transaction(function()use($dentistId,$appointmentDate,$durationMinutes,$serviceType,$correlationId){$d=Dentist::findOrFail($dentistId);$hours=$durationMinutes/60;$total=(int)($d->price_kopecks_per_hour*$hours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'dental_appt','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$a=DentalAppointment::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'dentist_id'=>$dentistId,'patient_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','appointment_date'=>$appointmentDate,'duration_minutes'=>$durationMinutes,'service_type'=>$serviceType,'tags'=>['dental'=>true]]);Log::channel('audit')->info('Dental appointment created',['appointment_id'=>$a->id,'correlation_id'=>$correlationId]);return $a;});
}
public function completeAppointment(int $appointmentId,string $correlationId=""):DentalAppointment{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($appointmentId,$correlationId){$a=DentalAppointment::findOrFail($appointmentId);if($a->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$a->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$a->payout_kopecks,'dental_payout',['correlation_id'=>$correlationId,'appointment_id'=>$a->id]);Log::channel('audit')->info('Dental appointment completed',['appointment_id'=>$a->id]);return $a;});}
public function cancelAppointment(int $appointmentId,string $correlationId=""):DentalAppointment{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($appointmentId,$correlationId){$a=DentalAppointment::findOrFail($appointmentId);if($a->status==='completed')throw new \RuntimeException("Cannot cancel",400);$a->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($a->payment_status==='completed')$this->wallet->credit(tenant()->id,$a->total_kopecks,'dental_refund',['correlation_id'=>$correlationId,'appointment_id'=>$a->id]);Log::channel('audit')->info('Dental appointment cancelled',['appointment_id'=>$a->id]);return $a;});}
public function getAppointment(int $appointmentId):DentalAppointment{return DentalAppointment::findOrFail($appointmentId);}
public function getUserAppointments(int $patientId){return DentalAppointment::where('patient_id',$patientId)->orderBy('created_at','desc')->take(10)->get();}
}

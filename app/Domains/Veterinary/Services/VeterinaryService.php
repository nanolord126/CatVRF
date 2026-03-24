<?php declare(strict_types=1);
namespace App\Domains\Veterinary\Services;
use App\Domains\Veterinary\Models\VeterinaryClinic;
use App\Domains\Veterinary\Models\VeterinaryAppointment;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class VeterinaryService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createAppointment(int $clinicId,$petName,$petType,$appointmentDate,$serviceType,string $correlationId=""):VeterinaryAppointment{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("vet:appt:".auth()->id(),10))throw new \RuntimeException("Too many",429);RateLimiter::hit("vet:appt:".auth()->id(),3600);
return DB::transaction(function()use($clinicId,$petName,$petType,$appointmentDate,$serviceType,$correlationId){$c=VeterinaryClinic::findOrFail($clinicId);$total=(int)($c->price_kopecks_per_hour*1);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'veterinary','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$a=VeterinaryAppointment::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'clinic_id'=>$clinicId,'owner_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','pet_name'=>$petName,'pet_type'=>$petType,'appointment_date'=>$appointmentDate,'service_type'=>$serviceType,'tags'=>['veterinary'=>true]]);Log::channel('audit')->info('Veterinary appointment created',['appointment_id'=>$a->id,'correlation_id'=>$correlationId]);return $a;});
}
public function completeAppointment(int $appointmentId,string $correlationId=""):VeterinaryAppointment{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($appointmentId,$correlationId){$a=VeterinaryAppointment::findOrFail($appointmentId);if($a->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$a->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$a->payout_kopecks,'vet_payout',['correlation_id'=>$correlationId,'appointment_id'=>$a->id]);Log::channel('audit')->info('Veterinary appointment completed',['appointment_id'=>$a->id]);return $a;});}
public function cancelAppointment(int $appointmentId,string $correlationId=""):VeterinaryAppointment{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($appointmentId,$correlationId){$a=VeterinaryAppointment::findOrFail($appointmentId);if($a->status==='completed')throw new \RuntimeException("Cannot cancel",400);$a->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($a->payment_status==='completed')$this->wallet->credit(tenant()->id,$a->total_kopecks,'vet_refund',['correlation_id'=>$correlationId,'appointment_id'=>$a->id]);Log::channel('audit')->info('Veterinary appointment cancelled',['appointment_id'=>$a->id]);return $a;});}
public function getAppointment(int $appointmentId):VeterinaryAppointment{return VeterinaryAppointment::findOrFail($appointmentId);}
public function getUserAppointments(int $ownerId){return VeterinaryAppointment::where('owner_id',$ownerId)->orderBy('created_at','desc')->take(10)->get();}
}

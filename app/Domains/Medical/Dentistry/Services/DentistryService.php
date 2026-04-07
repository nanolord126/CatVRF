<?php declare(strict_types=1);

/**
 * DentistryService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/dentistryservice
 */


namespace App\Domains\Medical\Dentistry\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class DentistryService
{

    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}
    public function createAppointment(int $dentistId,$appointmentDate,$durationMinutes,$serviceType,string $correlationId=""):DentalAppointment{$correlationId=$correlationId?:(string)Str::uuid();if($this->rateLimiter->tooManyAttempts("dental:appt:".$this->guard->id(),20))throw new \RuntimeException("Too many",429);$this->rateLimiter->hit("dental:appt:".$this->guard->id(),3600);
    return $this->db->transaction(function() use ($dentistId, $appointmentDate, $durationMinutes, $serviceType, $correlationId) {$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'dental_appt', amount: 0, correlationId: $correlationId ?? '');if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$a=DentalAppointment::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'dentist_id'=>$dentistId,'patient_id'=>$this->guard->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','appointment_date'=>$appointmentDate,'duration_minutes'=>$durationMinutes,'service_type'=>$serviceType,'tags'=>['dental'=>true]]);$this->logger->info('Dental appointment created',['appointment_id'=>$a->id,'correlation_id'=>$correlationId]);return $a;});
    }
    public function completeAppointment(int $appointmentId,string $correlationId=""):DentalAppointment{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($appointmentId,$correlationId){$a=DentalAppointment::findOrFail($appointmentId);if($a->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$a->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$a->payout_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['appointment_id'=>$a->id]);return $a;});}
    public function cancelAppointment(int $appointmentId,string $correlationId=""):DentalAppointment{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($appointmentId,$correlationId){$a=DentalAppointment::findOrFail($appointmentId);if($a->status==='completed')throw new \RuntimeException("Cannot cancel",400);$a->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($a->payment_status==='completed')$this->wallet->credit(tenant()->id,$a->total_kopecks,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,$correlationId, null, null, ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['appointment_id'=>$a->id]);return $a;});}
    public function getAppointment(int $appointmentId):DentalAppointment{return DentalAppointment::findOrFail($appointmentId);}
    public function getUserAppointments(int $patientId){return DentalAppointment::where('patient_id',$patientId)->orderBy('created_at','desc')->take(10)->get();}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

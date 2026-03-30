<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\TechSupport\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TechSupportService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}


    public function createTicket(int $specialistId,$issueType,$supportHours,$dueDate,string $correlationId=""):SupportTicket{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("tech:tick:".auth()->id(),20))throw new \RuntimeException("Too many",429);RateLimiter::hit("tech:tick:".auth()->id(),3600);


    return DB::transaction(function()use($specialistId,$issueType,$supportHours,$dueDate,$correlationId){$s=TechSupportSpecialist::findOrFail($specialistId);$total=(int)($s->price_kopecks_per_hour*$supportHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'tech_support','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$t=SupportTicket::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'specialist_id'=>$specialistId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','issue_type'=>$issueType,'support_hours'=>$supportHours,'due_date'=>$dueDate,'tags'=>['tech'=>true]]);Log::channel('audit')->info('Support ticket created',['ticket_id'=>$t->id,'correlation_id'=>$correlationId]);return $t;});


    }


    public function completeTicket(int $ticketId,string $correlationId=""):SupportTicket{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($ticketId,$correlationId){$t=SupportTicket::findOrFail($ticketId);if($t->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$t->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$t->payout_kopecks,'tech_payout',['correlation_id'=>$correlationId,'ticket_id'=>$t->id]);Log::channel('audit')->info('Support ticket completed',['ticket_id'=>$t->id]);return $t;});}


    public function cancelTicket(int $ticketId,string $correlationId=""):SupportTicket{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($ticketId,$correlationId){$t=SupportTicket::findOrFail($ticketId);if($t->status==='completed')throw new \RuntimeException("Cannot cancel",400);$t->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($t->payment_status==='completed')$this->wallet->credit(tenant()->id,$t->total_kopecks,'tech_refund',['correlation_id'=>$correlationId,'ticket_id'=>$t->id]);Log::channel('audit')->info('Support ticket cancelled',['ticket_id'=>$t->id]);return $t;});}


    public function getTicket(int $ticketId):SupportTicket{return SupportTicket::findOrFail($ticketId);}


    public function getUserTickets(int $clientId){return SupportTicket::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}

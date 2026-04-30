<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Corporate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Corporate\Entities\EmployeeMembership;

final class EmployeeMembershipModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_employee_memberships';

    protected $fillable = [
        'tenant_id',
        'corporate_enrollment_id',
        'client_id',
        'remaining_visits',
        'total_visits',
        'status',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function corporateEnrollment()
    {
        return $this->belongsTo(CorporateEnrollmentModel::class, 'corporate_enrollment_id');
    }

    public static function fromDomain(EmployeeMembership $membership): self
    {
        return new self([
            'id' => $membership->id > 0 ? $membership->id : null,
            'tenant_id' => $membership->tenantId,
            'corporate_enrollment_id' => $membership->corporateEnrollmentId,
            'client_id' => $membership->clientId,
            'remaining_visits' => $membership->remainingVisits,
            'total_visits' => $membership->totalVisits,
            'status' => $membership->status,
            'start_date' => $membership->startDate->toDateString(),
            'end_date' => $membership->endDate?->toDateString(),
            'notes' => $membership->notes,
        ]);
    }

    public function updateFromDomain(EmployeeMembership $membership): void
    {
        $this->remaining_visits = $membership->remainingVisits;
        $this->total_visits = $membership->totalVisits;
        $this->status = $membership->status;
        $this->start_date = $membership->startDate->toDateString();
        $this->end_date = $membership->endDate?->toDateString();
        $this->notes = $membership->notes;
    }

    public function toDomain(): EmployeeMembership
    {
        return new EmployeeMembership(
            id: $this->id,
            tenantId: $this->tenant_id,
            corporateEnrollmentId: $this->corporate_enrollment_id,
            clientId: $this->client_id,
            remainingVisits: $this->remaining_visits,
            totalVisits: $this->total_visits,
            status: $this->status,
            startDate: \Carbon\CarbonImmutable::parse($this->start_date),
            endDate: $this->end_date ? \Carbon\CarbonImmutable::parse($this->end_date) : null,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}

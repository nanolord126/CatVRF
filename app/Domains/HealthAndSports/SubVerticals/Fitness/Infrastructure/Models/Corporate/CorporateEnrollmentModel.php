<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Corporate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Corporate\Entities\CorporateEnrollment;

final class CorporateEnrollmentModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_corporate_enrollments';

    protected $fillable = [
        'tenant_id',
        'corporate_client_id',
        'corporate_package_id',
        'number_of_employees',
        'total_amount',
        'status',
        'start_date',
        'end_date',
        'invoice_number',
        'invoice_date',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'invoice_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function corporateClient()
    {
        return $this->belongsTo(CorporateClientModel::class, 'corporate_client_id');
    }

    public function corporatePackage()
    {
        return $this->belongsTo(CorporatePackageModel::class, 'corporate_package_id');
    }

    public static function fromDomain(CorporateEnrollment $enrollment): self
    {
        return new self([
            'id' => $enrollment->id > 0 ? $enrollment->id : null,
            'tenant_id' => $enrollment->tenantId,
            'corporate_client_id' => $enrollment->corporateClientId,
            'corporate_package_id' => $enrollment->corporatePackageId,
            'number_of_employees' => $enrollment->numberOfEmployees,
            'total_amount' => $enrollment->totalAmount,
            'status' => $enrollment->status->value,
            'start_date' => $enrollment->startDate->toDateString(),
            'end_date' => $enrollment->endDate->toDateString(),
            'invoice_number' => $enrollment->invoiceNumber,
            'invoice_date' => $enrollment->invoiceDate?->toDateString(),
            'paid_at' => $enrollment->paidAt,
            'notes' => $enrollment->notes,
        ]);
    }

    public function updateFromDomain(CorporateEnrollment $enrollment): void
    {
        $this->number_of_employees = $enrollment->numberOfEmployees;
        $this->total_amount = $enrollment->totalAmount;
        $this->status = $enrollment->status->value;
        $this->start_date = $enrollment->startDate->toDateString();
        $this->end_date = $enrollment->endDate->toDateString();
        $this->invoice_number = $enrollment->invoiceNumber;
        $this->invoice_date = $enrollment->invoiceDate?->toDateString();
        $this->paid_at = $enrollment->paidAt;
        $this->notes = $enrollment->notes;
    }

    public function toDomain(): CorporateEnrollment
    {
        return new CorporateEnrollment(
            id: $this->id,
            tenantId: $this->tenant_id,
            corporateClientId: $this->corporate_client_id,
            corporatePackageId: $this->corporate_package_id,
            numberOfEmployees: $this->number_of_employees,
            totalAmount: (float) $this->total_amount,
            status: \Modules\Fitness\Domain\Corporate\Enums\CorporateEnrollmentStatus::from($this->status),
            startDate: \Carbon\CarbonImmutable::parse($this->start_date),
            endDate: \Carbon\CarbonImmutable::parse($this->end_date),
            invoiceNumber: $this->invoice_number,
            invoiceDate: $this->invoice_date ? \Carbon\CarbonImmutable::parse($this->invoice_date) : null,
            paidAt: $this->paid_at ? \Carbon\CarbonImmutable::parse($this->paid_at) : null,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}

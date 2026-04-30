<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Corporate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Corporate\Entities\CorporateClient;

final class CorporateClientModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_corporate_clients';

    protected $fillable = [
        'tenant_id',
        'name',
        'inn',
        'legal_address',
        'contact_person',
        'hr_email',
        'phone_number',
        'contract_number',
        'contract_date',
        'contract_end_date',
        'notes',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'contract_end_date' => 'date',
    ];

    public static function fromDomain(CorporateClient $client): self
    {
        return new self([
            'id' => $client->id > 0 ? $client->id : null,
            'tenant_id' => $client->tenantId,
            'name' => $client->name,
            'inn' => $client->inn,
            'legal_address' => $client->legalAddress,
            'contact_person' => $client->contactPerson,
            'hr_email' => $client->hrEmail,
            'phone_number' => $client->phoneNumber,
            'contract_number' => $client->contractNumber,
            'contract_date' => $client->contractDate?->toDateString(),
            'contract_end_date' => $client->contractEndDate?->toDateString(),
            'notes' => $client->notes,
        ]);
    }

    public function updateFromDomain(CorporateClient $client): void
    {
        $this->name = $client->name;
        $this->inn = $client->inn;
        $this->legal_address = $client->legalAddress;
        $this->contact_person = $client->contactPerson;
        $this->hr_email = $client->hrEmail;
        $this->phone_number = $client->phoneNumber;
        $this->contract_number = $client->contractNumber;
        $this->contract_date = $client->contractDate?->toDateString();
        $this->contract_end_date = $client->contractEndDate?->toDateString();
        $this->notes = $client->notes;
    }

    public function toDomain(): CorporateClient
    {
        return new CorporateClient(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            inn: $this->inn,
            legalAddress: $this->legal_address,
            contactPerson: $this->contact_person,
            hrEmail: $this->hr_email,
            phoneNumber: $this->phone_number,
            contractNumber: $this->contract_number,
            contractDate: $this->contract_date ? \Carbon\CarbonImmutable::parse($this->contract_date) : null,
            contractEndDate: $this->contract_end_date ? \Carbon\CarbonImmutable::parse($this->contract_end_date) : null,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}

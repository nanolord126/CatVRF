<?php declare(strict_types=1);

namespace App\Domains\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Audit\Models\AuditLog;

final class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AuditLog $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'business_group_id' => $this->business_group_id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip_address' => $this->ip_address,
            'device_fingerprint' => $this->device_fingerprint,
            'correlation_id' => $this->correlation_id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

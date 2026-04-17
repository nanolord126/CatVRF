<?php declare(strict_types=1);

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Compliance\Models\ComplianceRecord;

final class ComplianceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ComplianceRecord $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'type' => $this->type,
            'document_id' => $this->document_id,
            'status' => $this->status,
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'response_data' => $this->response_data,
            'is_verified' => $this->isVerified(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

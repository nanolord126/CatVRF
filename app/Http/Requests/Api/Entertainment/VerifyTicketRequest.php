<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Entertainment;

/**
 * КАНОН 2026 — VERIFY TICKET REQUEST
 */
final class VerifyTicketRequest extends BaseEntertainmentRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'ticket_id' => ['required', 'string', 'uuid'],
        ]);
    }
}

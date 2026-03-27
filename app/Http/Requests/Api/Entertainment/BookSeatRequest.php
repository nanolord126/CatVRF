<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Entertainment;

/**
 * КАНОН 2026 — BOOK SEAT REQUEST
 */
final class BookSeatRequest extends BaseEntertainmentRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'event_id' => ['required', 'integer', 'exists:entertainment_events,id'],
            'seats' => ['required', 'array', 'min:1'],
            'seats.*.row' => ['required', 'integer'],
            'seats.*.col' => ['required', 'integer'],
        ]);
    }
}

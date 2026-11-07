<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Fret-specific counterpart of StoreBookingRequest: "quantity" here is a
 * parcel/cargo weight in kg (see FreightOfferController::book), which
 * needs a much higher ceiling than the generic 1-20 "quantity" (seats,
 * nights, tickets...) used by every other module.
 */
class StoreFreightBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:20000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

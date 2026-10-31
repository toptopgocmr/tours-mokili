<?php

namespace App\Http\Requests;

use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // L'ancienne app mobile n'envoyait rien : on retombe sur Peex
            // et sur le portefeuille deja verifie de l'utilisateur.
            'gateway' => strtolower((string) ($this->input('gateway') ?: config('payments.default', 'peex'))),
            'country_code' => strtoupper((string) ($this->input('country_code') ?: $this->user()?->wallet?->country_code ?: 'CG')),
            'phone' => $this->input('phone') ?: $this->user()?->wallet?->account_number,
        ]);
    }

    public function rules(): array
    {
        return [
            'gateway' => ['required', Rule::in(array_keys(PaymentGatewayManager::GATEWAYS))],
            'country_code' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'string', 'min:6', 'max:20'],
            'network' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Saisissez votre numero Mobile Money.',
            'gateway.in' => 'Moyen de paiement inconnu.',
        ];
    }
}

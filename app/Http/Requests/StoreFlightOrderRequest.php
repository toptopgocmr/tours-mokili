<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlightOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accepted_price' => ['nullable', 'numeric', 'min:0'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'passengers' => ['required', 'array', 'min:1', 'max:9'],
            'passengers.*.type' => ['required', 'in:adult,child,infant'],
            'passengers.*.title' => ['nullable', 'in:M,Mme,Mlle'],
            'passengers.*.first_name' => ['required', 'string', 'max:60', "regex:/^[\\pL '\\-]+$/u"],
            'passengers.*.last_name' => ['required', 'string', 'max:60', "regex:/^[\\pL '\\-]+$/u"],
            'passengers.*.birth_date' => ['required', 'date', 'before:today'],
            'passengers.*.gender' => ['nullable', 'in:M,F'],
            'passengers.*.nationality' => ['nullable', 'alpha', 'size:2'],
            'passengers.*.passport_number' => ['nullable', 'string', 'max:30'],
            'passengers.*.passport_expiry' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'passengers.*.first_name' => 'prenom du passager',
            'passengers.*.last_name' => 'nom du passager',
            'passengers.*.birth_date' => 'date de naissance',
            'passengers.*.passport_expiry' => 'expiration du passeport',
        ];
    }

    public function messages(): array
    {
        return [
            'passengers.*.first_name.regex' => 'Le prenom doit etre saisi comme sur le passeport (lettres uniquement).',
            'passengers.*.last_name.regex' => 'Le nom doit etre saisi comme sur le passeport (lettres uniquement).',
            'passengers.*.passport_expiry.after' => 'Le passeport doit etre valide.',
        ];
    }
}

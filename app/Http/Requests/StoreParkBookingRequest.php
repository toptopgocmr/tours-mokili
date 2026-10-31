<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Billets d'entree pour un parc (LOISIRS) : adultes + enfants + date de visite. */
class StoreParkBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adults' => ['required', 'integer', 'min:0', 'max:30'],
            'children' => ['nullable', 'integer', 'min:0', 'max:30'],
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (((int) $this->input('adults')) + ((int) $this->input('children')) < 1) {
                    $validator->errors()->add('adults', 'Choisissez au moins un billet.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'visit_date.after_or_equal' => 'La date de visite ne peut pas etre dans le passe.',
        ];
    }
}

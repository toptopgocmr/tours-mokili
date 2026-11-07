<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin' => ['required', 'alpha', 'size:3', 'exists:airports,iata'],
            'destination' => ['required', 'alpha', 'size:3', 'different:origin', 'exists:airports,iata'],
            'depart_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after_or_equal:depart_date'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:8'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:4', 'lte:adults'],
            'cabin' => ['nullable', 'in:economy,premium,business,first'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'origin' => strtoupper((string) $this->input('origin')),
            'destination' => strtoupper((string) $this->input('destination')),
        ]);
    }

    public function messages(): array
    {
        return [
            'origin.exists' => 'Aeroport de depart inconnu : choisissez dans la liste.',
            'destination.exists' => 'Aeroport d\'arrivee inconnu : choisissez dans la liste.',
            'destination.different' => 'Le depart et l\'arrivee doivent etre differents.',
            'depart_date.after_or_equal' => 'La date de depart ne peut pas etre passee.',
            'return_date.after_or_equal' => 'Le retour doit etre apres le depart.',
            'infants.lte' => 'Il faut au moins un adulte par bebe.',
        ];
    }
}

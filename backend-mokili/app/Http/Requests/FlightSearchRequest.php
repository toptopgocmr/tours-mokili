<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'origin' => strtoupper((string) $this->input('origin')),
            'destination' => strtoupper((string) $this->input('destination')),
            'trip_type' => $this->input('trip_type', $this->filled('return_date') ? 'roundtrip' : 'oneway'),
            'adults' => (int) $this->input('adults', 1),
            'children' => (int) $this->input('children', 0),
            'infants' => (int) $this->input('infants', 0),
            'cabin' => $this->input('cabin', 'economy'),
        ]);
    }

    public function rules(): array
    {
        $max = (int) config('flights.max_passengers', 9);

        return [
            'trip_type' => ['required', 'in:oneway,roundtrip'],
            'origin' => ['required', 'size:3', 'exists:airports,iata_code'],
            'destination' => ['required', 'size:3', 'different:origin', 'exists:airports,iata_code'],
            'depart_date' => ['required', 'date', 'after_or_equal:today', 'before:+361 days'],
            'return_date' => ['nullable', 'required_if:trip_type,roundtrip', 'date', 'after_or_equal:depart_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:'.$max],
            'children' => ['integer', 'min:0', 'max:'.$max],
            'infants' => ['integer', 'min:0', 'lte:adults'],
            'cabin' => ['required', 'in:'.implode(',', array_keys(config('flights.cabins')))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->integer('adults') + $this->integer('children') > (int) config('flights.max_passengers', 9)) {
                $v->errors()->add('adults', 'Maximum '.config('flights.max_passengers', 9).' passagers par reservation.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'destination.different' => 'La destination doit etre differente du depart.',
            'origin.exists' => 'Choisissez un aeroport de depart dans la liste.',
            'destination.exists' => "Choisissez un aeroport d'arrivee dans la liste.",
            'infants.lte' => 'Un bebe doit voyager avec un adulte.',
            'return_date.required_if' => 'Indiquez la date de retour.',
        ];
    }
}

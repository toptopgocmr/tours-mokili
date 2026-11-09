<?php

namespace App\Http\Requests;

use App\Services\Flights\Data\SearchCriteria;
use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function isMultiCity(): bool
    {
        return $this->input('trip_type') === 'multicity';
    }

    protected function prepareForValidation(): void
    {
        $common = [
            'trip_type' => $this->input('trip_type', $this->filled('return_date') ? 'roundtrip' : 'oneway'),
            'adults' => (int) $this->input('adults', 1),
            'children' => (int) $this->input('children', 0),
            'infants' => (int) $this->input('infants', 0),
            'cabin' => $this->input('cabin', 'economy'),
        ];

        if ($common['trip_type'] === 'multicity') {
            $legs = array_values(array_map(fn ($l) => [
                'origin' => strtoupper((string) ($l['origin'] ?? '')),
                'destination' => strtoupper((string) ($l['destination'] ?? '')),
                'date' => (string) ($l['date'] ?? ''),
            ], array_filter((array) $this->input('legs', []), 'is_array')));

            $this->merge($common + [
                'legs' => $legs,
                // Premier depart / derniere arrivee : resume de la recherche.
                'origin' => $legs[0]['origin'] ?? '',
                'destination' => $legs ? $legs[count($legs) - 1]['destination'] : '',
                'depart_date' => $legs[0]['date'] ?? '',
                'return_date' => null,
            ]);

            return;
        }

        $this->merge($common + [
            'origin' => strtoupper((string) $this->input('origin')),
            'destination' => strtoupper((string) $this->input('destination')),
        ]);
    }

    public function rules(): array
    {
        $max = (int) config('flights.max_passengers', 9);

        $passengers = [
            'trip_type' => ['required', 'in:oneway,roundtrip,multicity'],
            'adults' => ['required', 'integer', 'min:1', 'max:'.$max],
            'children' => ['integer', 'min:0', 'max:'.$max],
            'infants' => ['integer', 'min:0', 'lte:adults'],
            'cabin' => ['required', 'in:'.implode(',', array_keys(config('flights.cabins')))],
            'direct_only' => ['nullable', 'boolean'],
        ];

        if ($this->isMultiCity()) {
            return $passengers + [
                'legs' => ['required', 'array', 'min:2', 'max:'.SearchCriteria::MAX_LEGS],
                'legs.*.origin' => ['required', 'size:3', 'exists:airports,iata_code'],
                'legs.*.destination' => ['required', 'size:3', 'different:legs.*.origin', 'exists:airports,iata_code'],
                'legs.*.date' => ['required', 'date', 'after_or_equal:today', 'before:+361 days'],
                'origin' => ['required', 'size:3'],
                'destination' => ['required', 'size:3'],
                'depart_date' => ['required', 'date'],
            ];
        }

        return $passengers + [
            'origin' => ['required', 'size:3', 'exists:airports,iata_code'],
            'destination' => ['required', 'size:3', 'different:origin', 'exists:airports,iata_code'],
            'depart_date' => ['required', 'date', 'after_or_equal:today', 'before:+361 days'],
            'return_date' => ['nullable', 'required_if:trip_type,roundtrip', 'date', 'after_or_equal:depart_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->integer('adults') + $this->integer('children') > (int) config('flights.max_passengers', 9)) {
                $v->errors()->add('adults', 'Maximum '.config('flights.max_passengers', 9).' passagers par reservation.');
            }

            if ($this->isMultiCity()) {
                $legs = (array) $this->input('legs', []);
                for ($i = 1; $i < count($legs); $i++) {
                    if (! empty($legs[$i]['date']) && ! empty($legs[$i - 1]['date']) && $legs[$i]['date'] < $legs[$i - 1]['date']) {
                        $v->errors()->add("legs.$i.date", 'Vol '.($i + 1).' : la date doit etre posterieure ou egale a celle du vol '.$i.'.');
                    }
                }
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
            'legs.min' => 'Ajoutez au moins 2 vols pour un voyage multi-destinations.',
            'legs.max' => 'Maximum '.SearchCriteria::MAX_LEGS.' vols par voyage multi-destinations.',
            'legs.*.origin.required' => 'Choisissez un aeroport de depart pour chaque vol.',
            'legs.*.origin.exists' => 'Choisissez un aeroport de depart dans la liste.',
            'legs.*.destination.required' => "Choisissez un aeroport d'arrivee pour chaque vol.",
            'legs.*.destination.exists' => "Choisissez un aeroport d'arrivee dans la liste.",
            'legs.*.destination.different' => "Le depart et l'arrivee d'un vol doivent etre differents.",
            'legs.*.date.required' => 'Indiquez la date de chaque vol.',
            'legs.*.date.after_or_equal' => 'La date d\'un vol ne peut pas etre passee.',
        ];
    }
}

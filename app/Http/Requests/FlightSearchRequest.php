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
            // Multi-destinations : 2 a 6 vols simples, dates croissantes.
            'legs' => ['nullable', 'array', 'min:2', 'max:6'],
            'legs.*.origin' => ['required', 'alpha', 'size:3', 'exists:airports,iata'],
            'legs.*.destination' => ['required', 'alpha', 'size:3', 'exists:airports,iata', 'different:legs.*.origin'],
            'legs.*.date' => ['required', 'date', 'after_or_equal:today'],
            'direct_only' => ['nullable', 'boolean'],
            'return_date' => ['nullable', 'date', 'after_or_equal:depart_date'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:8'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:4', 'lte:adults'],
            'cabin' => ['nullable', 'in:economy,premium,business,first'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::normalize($this->all()));
    }

    /** Majuscules IATA ; en multi-destinations, le premier vol alimente origin/destination/depart_date. */
    public static function normalize(array $input): array
    {
        $input['origin'] = strtoupper((string) ($input['origin'] ?? ''));
        $input['destination'] = strtoupper((string) ($input['destination'] ?? ''));
        $input['direct_only'] = filter_var($input['direct_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! empty($input['legs']) && is_array($input['legs'])) {
            $legs = array_values(array_map(fn ($l) => [
                'origin' => strtoupper((string) ($l['origin'] ?? '')),
                'destination' => strtoupper((string) ($l['destination'] ?? '')),
                'date' => $l['date'] ?? null,
            ], $input['legs']));
            $input['legs'] = $legs;
            $input['origin'] = $legs[0]['origin'];
            $input['destination'] = $legs[0]['destination'];
            $input['depart_date'] = $legs[0]['date'];
            $input['return_date'] = null;
        }

        return $input;
    }

    public function messages(): array
    {
        return [
            'origin.exists' => 'Aeroport de depart inconnu : choisissez dans la liste.',
            'destination.exists' => 'Aeroport d\'arrivee inconnu : choisissez dans la liste.',
            'legs.*.destination.different' => 'Chaque vol doit avoir un depart et une arrivee differents.',
            'legs.min' => 'Ajoutez au moins deux vols pour une recherche multi-destinations.',
            'destination.different' => 'Le depart et l\'arrivee doivent etre differents.',
            'depart_date.after_or_equal' => 'La date de depart ne peut pas etre passee.',
            'return_date.after_or_equal' => 'Le retour doit etre apres le depart.',
            'infants.lte' => 'Il faut au moins un adulte par bebe.',
        ];
    }
}

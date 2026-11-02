<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlightBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:190'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'expected_total' => ['required', 'numeric', 'min:0'],
            'accept_conditions' => ['accepted'],
            'passengers' => ['required', 'array', 'min:1', 'max:'.(int) config('flights.max_passengers', 9) * 2],
            'passengers.*.type' => ['required', 'in:ADT,CHD,INF'],
            'passengers.*.title' => ['nullable', 'in:M.,Mme,Mlle,Enfant'],
            'passengers.*.first_name' => ['required', 'string', 'max:80', "regex:/^[\\pL\\s'\\-]+$/u"],
            'passengers.*.last_name' => ['required', 'string', 'max:80', "regex:/^[\\pL\\s'\\-]+$/u"],
            'passengers.*.gender' => ['nullable', 'in:M,F'],
            'passengers.*.birth_date' => ['required', 'date', 'before:today'],
            'passengers.*.nationality' => ['nullable', 'string', 'size:2'],
            'passengers.*.document_type' => ['nullable', 'in:passport,id_card'],
            'passengers.*.document_number' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\\s]+$/'],
            'passengers.*.document_expiry' => ['nullable', 'date', 'after:today'],
            'passengers.*.document_country' => ['nullable', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'accept_conditions.accepted' => 'Vous devez accepter les conditions tarifaires et d\'annulation.',
            'passengers.*.first_name.regex' => 'Prenom : lettres uniquement, comme sur le passeport.',
            'passengers.*.last_name.regex' => 'Nom : lettres uniquement, comme sur le passeport.',
            'passengers.*.birth_date.required' => 'La date de naissance de chaque passager est obligatoire.',
        ];
    }

    /** Coherence age / type de passager a la date du depart. */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $offer = $this->route('flightOffer');
            $depart = $offer?->search?->depart_date;
            if (! $depart) {
                return;
            }
            foreach ((array) $this->input('passengers') as $i => $p) {
                if (empty($p['birth_date']) || empty($p['type'])) {
                    continue;
                }
                try {
                    $age = \Carbon\Carbon::parse($p['birth_date'])->diffInYears($depart);
                } catch (\Throwable) {
                    continue;
                }
                $ok = match ($p['type']) {
                    'INF' => $age < 2,
                    'CHD' => $age >= 2 && $age < 12,
                    default => $age >= 12,
                };
                if (! $ok) {
                    $v->errors()->add("passengers.$i.birth_date", "La date de naissance ne correspond pas au type de passager (adulte 12+, enfant 2-11, bebe < 2 ans).");
                }
            }
        });
    }
}

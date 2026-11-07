<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Champs adaptes au mode : aerien (poids/dimensions), maritime (FCL/LCL, conteneur, volume), routier (poids/volume/trajet). */
class FreightQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mode = $this->input('mode');

        return [
            'mode' => ['required', 'in:air,mer,route'],
            'origin_country' => ['required', 'string', 'size:2'],
            'origin_city' => ['required', 'string', 'max:255'],
            'destination_country' => ['required', 'string', 'size:2'],
            'destination_city' => ['required', 'string', 'max:255'],
            'cargo_type' => ['required', 'in:colis,marchandises,vehicule,conteneur'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'weight_kg' => [$mode === 'mer' && $this->input('load_type') === 'FCL' ? 'nullable' : 'required', 'numeric', 'min:0.1', 'max:10000000'],
            'volume_m3' => [$mode === 'mer' && $this->input('load_type') === 'LCL' ? 'required' : 'nullable', 'numeric', 'min:0.001', 'max:100000'],
            'dimensions' => [$mode === 'air' ? 'required' : 'nullable', 'string', 'max:120'],
            'packages' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'load_type' => [$mode === 'mer' ? 'required' : 'nullable', 'in:FCL,LCL'],
            'container_type' => [$mode === 'mer' && $this->input('load_type') === 'FCL' ? 'required' : 'nullable', 'string', 'max:20'],
            'ready_date' => ['nullable', 'date'],
            'desired_pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'desired_delivery_date' => ['nullable', 'date', 'after_or_equal:desired_pickup_date'],
            'services' => ['nullable', 'array'],
            'services.*' => ['in:pickup,packing,insurance,customs,storage,delivery'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'dangerous_goods' => ['boolean'],
            'offer_slug' => ['nullable', 'string', 'exists:freight_offers,slug'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Decrivez precisement la marchandise (10 caracteres minimum).',
            'dimensions.required' => 'Le fret aerien necessite les dimensions (LxlxH cm).',
            'volume_m3.required' => 'Le groupage maritime (LCL) necessite le volume en m3.',
            'container_type.required' => 'Indiquez le type de conteneur (ex: 20GP, 40HC).',
        ];
    }

    /** Donnees pretes pour QuoteRequest::create (sans le slug d'offre). */
    public function payload(): array
    {
        return collect($this->validated())->except('offer_slug')->all();
    }
}

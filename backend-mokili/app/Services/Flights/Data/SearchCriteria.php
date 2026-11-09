<?php

namespace App\Services\Flights\Data;

use Carbon\CarbonImmutable;

final class SearchCriteria
{
    public const MAX_LEGS = 6;

    /**
     * $legs : trajets d'une recherche multi-destinations (vide sinon),
     * chacun ['origin' => IATA, 'destination' => IATA, 'date' => CarbonImmutable].
     */
    public function __construct(
        public readonly string $origin,
        public readonly string $destination,
        public readonly CarbonImmutable $departDate,
        public readonly ?CarbonImmutable $returnDate = null,
        public readonly int $adults = 1,
        public readonly int $children = 0,
        public readonly int $infants = 0,
        public readonly string $cabin = 'economy',
        public readonly array $legs = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $type = $data['trip_type'] ?? 'oneway';

        if ($type === 'multicity' && ! empty($data['legs']) && is_array($data['legs'])) {
            $legs = array_values(array_map(fn ($l) => [
                'origin' => strtoupper((string) $l['origin']),
                'destination' => strtoupper((string) $l['destination']),
                'date' => CarbonImmutable::parse($l['date'])->startOfDay(),
            ], array_slice($data['legs'], 0, self::MAX_LEGS)));

            $first = $legs[0];
            $last = $legs[count($legs) - 1];

            return new self(
                $first['origin'],
                $last['destination'],
                $first['date'],
                null,
                (int) ($data['adults'] ?? 1),
                (int) ($data['children'] ?? 0),
                (int) ($data['infants'] ?? 0),
                $data['cabin'] ?? 'economy',
                $legs,
            );
        }

        $return = $type === 'roundtrip' && ! empty($data['return_date'])
            ? CarbonImmutable::parse($data['return_date'])->startOfDay()
            : null;

        return new self(
            strtoupper($data['origin']),
            strtoupper($data['destination']),
            CarbonImmutable::parse($data['depart_date'])->startOfDay(),
            $return,
            (int) ($data['adults'] ?? 1),
            (int) ($data['children'] ?? 0),
            (int) ($data['infants'] ?? 0),
            $data['cabin'] ?? 'economy',
        );
    }

    public function isRoundTrip(): bool
    {
        return $this->returnDate !== null && ! $this->isMultiCity();
    }

    public function isMultiCity(): bool
    {
        return count($this->legs) >= 2;
    }

    public function tripType(): string
    {
        return $this->isMultiCity() ? 'multicity' : ($this->isRoundTrip() ? 'roundtrip' : 'oneway');
    }

    /**
     * Trajets a rechercher, quel que soit le type de voyage :
     * aller simple = 1, aller-retour = 2, multi-destinations = N.
     *
     * @return array<int, array{origin: string, destination: string, date: CarbonImmutable}>
     */
    public function itinerary(): array
    {
        if ($this->isMultiCity()) {
            return $this->legs;
        }

        $legs = [['origin' => $this->origin, 'destination' => $this->destination, 'date' => $this->departDate]];
        if ($this->isRoundTrip()) {
            $legs[] = ['origin' => $this->destination, 'destination' => $this->origin, 'date' => $this->returnDate];
        }

        return $legs;
    }

    public function seatCount(): int
    {
        return $this->adults + $this->children; // les bebes voyagent sur les genoux
    }

    public function toArray(): array
    {
        return [
            'trip_type' => $this->tripType(),
            'origin' => $this->origin,
            'destination' => $this->destination,
            'depart_date' => $this->departDate->toDateString(),
            'return_date' => $this->isRoundTrip() ? $this->returnDate?->toDateString() : null,
            'legs' => $this->isMultiCity()
                ? array_map(fn ($l) => ['origin' => $l['origin'], 'destination' => $l['destination'], 'date' => $l['date']->toDateString()], $this->legs)
                : null,
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'cabin' => $this->cabin,
        ];
    }
}

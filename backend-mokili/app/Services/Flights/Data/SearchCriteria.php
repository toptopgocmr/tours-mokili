<?php

namespace App\Services\Flights\Data;

use Carbon\CarbonImmutable;

final class SearchCriteria
{
    public function __construct(
        public readonly string $origin,
        public readonly string $destination,
        public readonly CarbonImmutable $departDate,
        public readonly ?CarbonImmutable $returnDate = null,
        public readonly int $adults = 1,
        public readonly int $children = 0,
        public readonly int $infants = 0,
        public readonly string $cabin = 'economy',
    ) {}

    public static function fromArray(array $data): self
    {
        $return = ($data['trip_type'] ?? 'oneway') === 'roundtrip' && ! empty($data['return_date'])
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
        return $this->returnDate !== null;
    }

    public function seatCount(): int
    {
        return $this->adults + $this->children; // les bebes voyagent sur les genoux
    }

    public function toArray(): array
    {
        return [
            'trip_type' => $this->isRoundTrip() ? 'roundtrip' : 'oneway',
            'origin' => $this->origin,
            'destination' => $this->destination,
            'depart_date' => $this->departDate->toDateString(),
            'return_date' => $this->returnDate?->toDateString(),
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'cabin' => $this->cabin,
        ];
    }
}

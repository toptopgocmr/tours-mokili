<?php

namespace App\Services\Loisirs;

use App\Models\Booking;
use App\Models\Loisirs\AttractionPark;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Cree la reservation (billets) d'un parc - partage entre le site web
 * (Loisirs\BookingController) et l'API mobile (Api\AttractionParkController).
 */
class ParkBookingService
{
    public function create(AttractionPark $park, int $userId, array $data): Booking
    {
        if (! $park->is_active || ! $park->is_bookable) {
            throw ValidationException::withMessages([
                'adults' => "Ce parc n'est pas encore reservable en ligne (tarif communique sur place).",
            ]);
        }

        $adults = (int) ($data['adults'] ?? 0);
        $children = (int) ($data['children'] ?? 0);
        $adultPrice = (float) $park->price_adult;
        $childPrice = $park->childPrice();
        $total = $adults * $adultPrice + $children * $childPrice;
        $visitDate = Carbon::parse($data['visit_date'])->startOfDay();

        return Booking::create([
            'user_id' => $userId,
            'bookable_type' => AttractionPark::class,
            'bookable_id' => $park->id,
            'quantity' => $adults + $children,
            'unit_price' => $adultPrice,
            'total_amount' => $total,
            'currency' => $park->currency,
            'starts_at' => $visitDate,
            'ends_at' => $visitDate->copy()->endOfDay(),
            'status' => 'awaiting_payment',
            'meta' => [
                'park' => $park->name,
                'city' => $park->city,
                'adults' => $adults,
                'children' => $children,
                'child_price' => $childPrice,
            ],
            'notes' => $data['notes'] ?? null,
        ]);
    }
}

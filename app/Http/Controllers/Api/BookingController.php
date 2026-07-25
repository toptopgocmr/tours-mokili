<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Voyage\TravelOffer;
use App\Services\Peex\PeexClient;
use App\Services\Peex\PeexException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->bookings()->with('bookable')->latest()->paginate(10)
        );
    }

    public function store(StoreBookingRequest $request, TravelOffer $travelOffer): JsonResponse
    {
        $quantity = $request->integer('quantity');
        $unitPrice = $travelOffer->discounted_price;

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'bookable_type' => TravelOffer::class,
            'bookable_id' => $travelOffer->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'currency' => $travelOffer->currency,
            'starts_at' => $travelOffer->departure_at,
            'ends_at' => $travelOffer->return_at,
            'status' => 'awaiting_payment',
        ]);

        return response()->json($booking, 201);
    }

    /**
     * Mobile counterpart of App\Http\Controllers\CheckoutController::pay.
     * Requires the user's wallet to already be Peex-verified (see
     * WalletVerificationController::store / EnsureWalletVerified middleware).
     */
    public function pay(Request $request, Booking $booking, PeexClient $peex): JsonResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $wallet = $request->user()->wallet;

        if (! $wallet || ! $wallet->peex_verified_at) {
            return response()->json([
                'message' => 'Veuillez verifier votre portefeuille Peex avant de payer.',
            ], 409);
        }

        try {
            $payment = DB::transaction(function () use ($booking) {
                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'provider' => 'peex',
                    'method' => 'mobile_money',
                    'amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                    'status' => 'processing',
                ]);

                $booking->update(['status' => 'confirmed']);

                return $payment;
            });

            $response = $peex->requestPayment([
                'countryCode' => $wallet->country_code,
                'accountNumber' => $wallet->account_number,
                'amount' => (float) $booking->total_amount,
                'currency' => $booking->currency,
                'track_id' => $payment->id,
            ]);

            $payment->update([
                'status' => 'paid',
                'peex_track_id' => $response['request']['track_id'] ?? null,
                'peex_request_id' => $response['request']['id'] ?? null,
                'peex_response' => $response,
                'paid_at' => now(),
            ]);

            return response()->json(['booking' => $booking->fresh(), 'payment' => $payment]);
        } catch (PeexException $e) {
            $booking->update(['status' => 'awaiting_payment']);

            return response()->json(['message' => $e->getMessage()], $e->statusCode ?: 502);
        }
    }
}

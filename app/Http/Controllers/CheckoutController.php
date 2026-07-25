<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Peex\PeexClient;
use App\Services\Peex\PeexException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared checkout flow for every service module (bookings are
 * polymorphic - see App\Models\Booking).
 *
 * Step 1: /checkout/{booking}       -> show summary + Peex wallet status
 * Step 2: POST /wallet/verify       -> App\Http\Controllers\Api\WalletVerificationController
 * Step 3: POST /checkout/{booking}/pay (behind "peex.verified" middleware)
 */
class CheckoutController extends Controller
{
    public function show(Booking $booking): Response
    {
        abort_unless($booking->user_id === Auth::id(), 403);

        return Inertia::render('Checkout', [
            'booking' => $booking->load('bookable'),
            'wallet' => Auth::user()->wallet,
        ]);
    }

    public function pay(Booking $booking, PeexClient $peex): RedirectResponse
    {
        abort_unless($booking->user_id === Auth::id(), 403);

        $wallet = Auth::user()->wallet;

        try {
            $payment = DB::transaction(function () use ($booking, $wallet) {
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

            // Sandbox note: Peex fixes every amount at 10 FCFA regardless of
            // the amount sent (see docs > Base URL & Resources).
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

            return redirect()->route('checkout.show', $booking)
                ->with('success', 'Paiement effectue avec succes. Reservation confirmee !');
        } catch (PeexException $e) {
            $booking->update(['status' => 'awaiting_payment']);

            return back()->with('error', 'Paiement Peex echoue : '.$e->getMessage());
        }
    }
}

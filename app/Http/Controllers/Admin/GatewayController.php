<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GatewaySetting;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reglage des moyens de paiement (/admin/passerelles) : etat de
 * configuration de chaque passerelle (cles presentes ?), activation de la
 * collecte / du decaissement, pays preferes, URL de webhook a declarer.
 */
class GatewayController extends Controller
{
    public function index(PaymentGatewayManager $manager, PaymentService $payments): Response
    {
        $settings = $manager->settings();

        $gateways = collect($manager->names())->map(function ($name) use ($manager, $settings, $payments) {
            $gateway = $manager->gateway($name);
            $setting = $settings->get($name);

            return [
                'key' => $name,
                'label' => $gateway->label(),
                'configured' => $gateway->isConfigured(),
                'status_check' => $gateway->supportsStatusCheck(),
                'countries' => config("payments.gateways.{$name}.countries", []),
                'collect_enabled' => $setting?->collect_enabled ?? true,
                'payout_enabled' => $setting?->payout_enabled ?? true,
                'preferred_countries' => $setting?->preferred_countries ?? [],
                'webhook_url' => $payments->webhookUrl($name),
                'env' => match ($name) {
                    'peex' => ['PEEX_ENV', 'PEEX_SANDBOX_KEY', 'PEEX_PRODUCTION_KEY', 'PEEX_PAYOUT_PATH', 'PEEX_WEBHOOK_SECRET'],
                    'pawapay' => ['PAWAPAY_ENV', 'PAWAPAY_API_TOKEN'],
                    'maishapay' => ['MAISHAPAY_GATEWAY_MODE', 'MAISHAPAY_PUBLIC_KEY', 'MAISHAPAY_SECRET_KEY', 'MAISHAPAY_WEBHOOK_SECRET', 'MAISHAPAY_AMOUNT_MULTIPLIER'],
                    'digitwace' => ['DIGITWACE_BASE_URL', 'DIGITWACE_API_KEY', 'DIGITWACE_API_SECRET', 'DIGITWACE_COLLECT_PATH', 'DIGITWACE_PAYOUT_PATH', 'DIGITWACE_STATUS_PATH', 'DIGITWACE_WEBHOOK_SECRET'],
                    default => [],
                },
            ];
        });

        return Inertia::render('Admin/Gateways/Index', [
            'gateways' => $gateways,
            'countries' => config('payments.country_names'),
        ]);
    }

    public function update(Request $request, string $gateway): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless(array_key_exists($gateway, PaymentGatewayManager::GATEWAYS), 404);

        $data = $request->validate([
            'collect_enabled' => ['required', 'boolean'],
            'payout_enabled' => ['required', 'boolean'],
            'preferred_countries' => ['array'],
            'preferred_countries.*' => [Rule::in(array_keys(config('payments.country_names')))],
        ]);

        // Un pays ne peut avoir qu'une passerelle preferee.
        $countries = $data['preferred_countries'] ?? [];
        GatewaySetting::where('gateway', '!=', $gateway)->get()->each(function (GatewaySetting $other) use ($countries) {
            $other->update(['preferred_countries' => array_values(array_diff($other->preferred_countries ?? [], $countries))]);
        });

        GatewaySetting::updateOrCreate(['gateway' => $gateway], $data);

        return back()->with('success', 'Reglages enregistres.');
    }
}

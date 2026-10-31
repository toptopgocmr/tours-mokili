<?php

namespace App\Services\Payments;

use App\Models\GatewaySetting;
use App\Services\Payments\Gateways\AbstractGateway;
use App\Services\Payments\Gateways\DigitwaceGateway;
use App\Services\Payments\Gateways\MaishaPayGateway;
use App\Services\Payments\Gateways\PawaPayGateway;
use App\Services\Payments\Gateways\PeexGateway;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Point d'entree unique vers les 4 passerelles : resolution par nom,
 * passerelles disponibles par pays / par sens (collecte ou decaissement),
 * passerelle preferee par pays (reglage back-office > config > defaut).
 */
class PaymentGatewayManager
{
    public const GATEWAYS = [
        'peex' => PeexGateway::class,
        'pawapay' => PawaPayGateway::class,
        'maishapay' => MaishaPayGateway::class,
        'digitwace' => DigitwaceGateway::class,
    ];

    protected array $resolved = [];

    protected ?Collection $settings = null;

    public function __construct(protected Container $container) {}

    public function gateway(string $name): AbstractGateway
    {
        $name = strtolower($name);

        if (! isset(self::GATEWAYS[$name])) {
            throw new InvalidArgumentException("Passerelle de paiement inconnue : {$name}");
        }

        return $this->resolved[$name] ??= $this->container->make(self::GATEWAYS[$name]);
    }

    public function names(): array
    {
        return array_keys(self::GATEWAYS);
    }

    public function settings(): Collection
    {
        if ($this->settings === null) {
            $this->settings = Schema::hasTable('gateway_settings')
                ? GatewaySetting::query()->get()->keyBy('gateway')
                : collect();
        }

        return $this->settings;
    }

    /** Activee dans le back-office ET cles API presentes ? ($type = collect | payout) */
    public function isAvailable(string $name, string $type = 'collect', ?string $country = null): bool
    {
        if (! isset(self::GATEWAYS[$name]) || ! config("payments.gateways.{$name}.{$type}", true)) {
            return false;
        }

        $setting = $this->settings()->get($name);
        $enabled = $setting ? (bool) $setting->{$type.'_enabled'} : true;

        if (! $enabled || ! $this->gateway($name)->isConfigured()) {
            return false;
        }

        return $country === null
            || in_array(strtoupper($country), config("payments.gateways.{$name}.countries", []), true);
    }

    /**
     * Passerelles utilisables pour un pays, dans l'ordre de preference :
     * 1) ordre fixe par pays (config payments.priority, ex. Congo :
     *    MaishaPay > Digitwace > Peex) pour la collecte,
     * 2) sinon passerelle recommandee (back-office) puis position.
     */
    public function availableFor(string $country, string $type = 'collect'): array
    {
        $country = strtoupper($country);
        $preferred = $this->preferredFor($country);
        $priority = $type === 'collect' ? config("payments.priority.{$country}", []) : [];

        return collect($this->names())
            ->filter(fn ($n) => $this->isAvailable($n, $type, $country))
            ->sortBy(function ($n) use ($priority, $preferred) {
                $rank = array_search($n, $priority, true);
                if ($rank !== false) {
                    return $rank;
                }

                return $n === $preferred ? 50 : 100 + ($this->settings()->get($n)?->position ?? 99);
            })
            ->values()
            ->all();
    }

    public function preferredFor(string $country): string
    {
        $country = strtoupper($country);

        // Un ordre fixe pour le pays l'emporte : la premiere passerelle
        // disponible de cette liste est la passerelle par defaut.
        foreach (config("payments.priority.{$country}", []) as $name) {
            if ($this->isAvailable($name, 'collect', $country)) {
                return $name;
            }
        }

        foreach ($this->settings()->sortBy('position') as $setting) {
            if (in_array($country, $setting->preferred_countries ?? [], true)) {
                return $setting->gateway;
            }
        }

        return config("payments.routing.{$country}", config('payments.default', 'peex'));
    }

    /** Operateurs Mobile Money d'un pays, avec la liste des passerelles qui les acceptent. */
    public function networksFor(string $country): array
    {
        return collect(config('payments.networks.'.strtoupper($country), []))
            ->map(fn ($n, $key) => ['key' => $key, 'label' => $n['label']])
            ->values()
            ->all();
    }

    /** Catalogue envoye au site web et a l'app mobile pour construire l'ecran de paiement. */
    public function catalogue(string $type = 'collect'): array
    {
        $countries = collect(config('payments.country_names'))
            ->map(function ($name, $code) use ($type) {
                return [
                    'code' => $code,
                    'name' => $name,
                    'dial_code' => config("payments.dial_codes.{$code}"),
                    'networks' => $this->networksFor($code),
                    'gateways' => $this->availableFor($code, $type),
                    'preferred' => $this->preferredFor($code),
                ];
            })
            ->filter(fn ($c) => count($c['gateways']) > 0)
            ->values();

        $gateways = collect($this->names())->map(fn ($n) => [
            'key' => $n,
            'label' => $this->gateway($n)->label(),
            'available' => $this->isAvailable($n, $type),
        ])->values();

        return ['countries' => $countries, 'gateways' => $gateways];
    }

    /** Normalise un numero en MSISDN international sans "+" (ex: 242061234567). */
    public static function normalizePhone(string $phone, string $country): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        $dial = (string) config('payments.dial_codes.'.strtoupper($country), '');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($dial !== '' && ! str_starts_with($digits, $dial)) {
            // Numero local : on retire un eventuel 0 initial sauf au Congo,
            // ou les numeros mobiles commencent par 0 (06 / 05 / 04).
            if (strtoupper($country) !== 'CG') {
                $digits = ltrim($digits, '0');
            }
            $digits = $dial.$digits;
        }

        return $digits;
    }
}

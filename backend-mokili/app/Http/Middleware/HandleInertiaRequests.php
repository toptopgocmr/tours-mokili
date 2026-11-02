<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'services' => fn () => \App\Models\ServiceCategory::query()
                ->orderBy('position')
                ->get(['id', 'slug', 'name', 'icon', 'color']),
            // Libelles des rubriques et filtres (config/catalogue.php).
            'catalogue' => fn () => config('catalogue'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()->orderBy('nom');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%")
                    ->orWhere('telephone', 'like', "%{$q}%");
            });
        }

        return response()->json($query->limit(30)->get());
    }
}

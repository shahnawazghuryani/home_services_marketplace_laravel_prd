<?php

namespace App\Http\Controllers;

use App\Models\Provider;

class ProviderController extends Controller
{
    public function show(int $provider)
    {
        $provider = Provider::with(['user', 'services.category', 'reviews.customer'])
            ->findOrFail($provider);

        $locationLabel = collect([
            $provider->service_area,
            $provider->user->city,
            $provider->user->address,
        ])->filter()->implode(', ');

        return view('providers.show', [
            'provider' => $provider,
            'locationLabel' => $locationLabel,
            'providerMapUrl' => 'https://www.google.com/maps?q=' . urlencode($locationLabel) . '&output=embed',
            'providerMapSearchUrl' => 'https://www.google.com/maps/search/?api=1&query=' . urlencode($locationLabel),
        ]);
    }
}

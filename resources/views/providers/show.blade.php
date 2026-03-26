@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container grid grid-2">
        <div class="card map-card">
            <div>
                <span class="badge">{{ $provider->service_area }}</span>
                <h1>{{ $provider->user->name }}</h1>
                <p>{{ $provider->bio }}</p>
                <p class="muted">{{ $provider->experience_years }} years experience - PKR {{ number_format($provider->hourly_rate) }}/hr - {{ $provider->availability }}</p>
                <p class="muted">Approval: {{ $provider->approved_at ? 'Approved' : 'Pending admin approval' }}</p>
            </div>
            <div class="notice">
                <strong>Phone</strong>
                <div>{{ $provider->user->phone }}</div>
            </div>
            <div class="stack-actions">
                <a class="btn secondary" href="tel:{{ preg_replace('/\D+/', '', $provider->user->phone) }}">Call now</a>
                <a class="btn brand" href="https://wa.me/{{ preg_replace('/\D+/', '', $provider->user->phone) }}" target="_blank" rel="noopener">WhatsApp</a>
            </div>
            <div class="location-row">
                <div class="notice">{{ $locationLabel }}</div>
                <a class="location-link" href="{{ $providerMapSearchUrl }}" target="_blank" rel="noopener">Open in Google Maps</a>
            </div>
            <iframe class="map-embed" src="{{ $providerMapUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Provider location map"></iframe>
        </div>
        <div class="card">
            <h3>Services by this provider</h3>
            <div class="list">
                @foreach($provider->services as $service)
                    <a class="notice" href="{{ route('services.show', $service->slug) }}">{{ $service->title }} - {{ $provider->user->city }} - PKR {{ number_format($service->price) }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
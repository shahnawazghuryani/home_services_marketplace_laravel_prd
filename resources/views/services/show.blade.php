@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container detail-grid">
        <div class="detail-panel">
            @if($service->image_path)
                <img class="detail-cover" src="{{ asset($service->image_path) }}" alt="{{ $service->title }}">
            @else
                <div class="detail-cover"></div>
            @endif
            <span class="badge">{{ $service->category->name }}</span>
            <h1 style="margin-bottom:10px;">{{ $service->title }}</h1>
            <p class="muted" style="font-size:1.04rem;">{{ $service->short_description }}</p>
            <div class="detail-meta">
                <div class="meta-box"><strong>Price</strong><div>PKR {{ number_format($service->price) }}</div></div>
                <div class="meta-box"><strong>Duration</strong><div>{{ $service->duration_minutes }} minutes</div></div>
                <div class="meta-box"><strong>Provider</strong><div>{{ $service->provider->user->name }}</div></div>
            </div>
            <div class="notice">{{ $service->description }}</div>
            <div class="notice" style="margin-top:16px;">Service area: {{ $providerLocationLabel }}</div>
            <div class="notice" style="margin-top:16px;">
                <strong>Phone</strong>
                <div>{{ $service->provider->user->phone }}</div>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;">
                @auth
                    @if(auth()->user()->isCustomer())
                        <a class="btn brand" href="{{ route('bookings.create', $service->slug) }}">Book this service</a>
                    @endif
                @else
                    <a class="btn brand" href="{{ route('login') }}">Login to book</a>
                @endauth
                <a class="btn secondary" href="tel:{{ preg_replace('/\D+/', '', $service->provider->user->phone) }}">Call provider</a>
                <a class="btn brand" href="https://wa.me/{{ preg_replace('/\D+/', '', $service->provider->user->phone) }}" target="_blank" rel="noopener">WhatsApp</a>
                <a class="btn secondary" href="{{ route('providers.show', $service->provider->id) }}">View provider</a>
            </div>
        </div>
        <div class="card map-card">
            <div>
                <h3>Provider location</h3>
                <p class="muted">Location ko map par dekhein aur area quickly samajh lein.</p>
            </div>
            <div class="location-row">
                <span class="badge">{{ $service->provider->user->city }}</span>
                <a class="location-link" href="{{ $providerMapSearchUrl }}" target="_blank" rel="noopener">Open in Google Maps</a>
            </div>
            <div class="notice">{{ $providerLocationLabel }}</div>
            <iframe class="map-embed" src="{{ $providerMapUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Provider location map"></iframe>
            <div>
                <h3>Related services</h3>
                <p class="muted">Isi category me aur bhi options available hain.</p>
                <div class="list">
                    @foreach($relatedServices as $related)
                        <a class="notice" href="{{ route('services.show', $related->slug) }}">{{ $related->title }} by {{ $related->provider->user->name }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
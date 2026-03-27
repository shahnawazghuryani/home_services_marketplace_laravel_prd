@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container search-layout">
        <div class="search-shell">
            <div class="search-panel">
                <form method="GET" action="{{ route('services.index') }}">
                    <datalist id="location-suggestions-services">
                        @foreach($locationSuggestions as $suggestion)
                            <option value="{{ $suggestion }}"></option>
                        @endforeach
                    </datalist>
                    <div class="hero-search-grid">
                        <label style="margin-bottom:0;">Search service or provider
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="AC repair, plumbing, cleaning">
                        </label>
                        <label style="margin-bottom:0;">Location
                            <div class="location-input">
                                <input type="text" name="location" list="location-suggestions-services" value="{{ request('location') }}" placeholder="Karachi, DHA, Clifton">
                                @auth
                                    <button class="btn secondary location-trigger" type="button" data-target="input[name=&quot;location&quot;]" data-location="{{ auth()->user()->city }}">Use saved city</button>
                                @endauth
                            </div>
                        </label>
                        <label style="margin-bottom:0;">Category
                            <select name="category">
                                <option value="">All categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div style="display:flex;align-items:end;gap:10px;">
                            <button class="btn brand" type="submit" style="width:100%;">Search</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="location-sidebar">
                <div class="card">
                    <div class="location-row">
                        <strong>Current location</strong>
                        <button class="btn secondary current-location-button" type="button" data-target="input[name=&quot;location&quot;]">Detect now</button>
                    </div>
                    <p class="muted current-location-text" style="margin:10px 0 0;">
                        @if(request('location'))
                            Current search: {{ request('location') }}
                        @elseif(auth()->check())
                            Saved city: {{ auth()->user()->city }}
                        @else
                            Browser se location detect ya suggestion choose karein.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="results-head">
            <div>
                <h2 style="margin:0 0 8px;">Services for users</h2>
                <div class="search-summary">{{ $services->count() }} result(s) found{{ request('search') ? ' for "' . request('search') . '"' : '' }}{{ request('location') ? ' near "' . request('location') . '"' : '' }}</div>
            </div>
            <a class="btn secondary" href="{{ route('services.index') }}">Clear filters</a>
        </div>

        <div class="grid grid-3">
            @forelse($services as $service)
                <div class="card service-card">
                    @if($service->image_path)
                        <img class="service-thumb" src="{{ asset($service->image_path) }}" alt="{{ $service->title }}">
                    @else
                        <div class="service-thumb"></div>
                    @endif
                    <div class="service-card-body">
                        <span class="badge">{{ $service->category->name }}</span>
                        <h3>{{ $service->title }}</h3>
                        <p>{{ $service->short_description }}</p>
                        <div class="notice">{{ $service->provider->service_area }} - {{ $service->provider->user->city }} - {{ $service->duration_minutes }} mins</div>
                        <div class="provider-strip">
                            <span class="avatar-pill">{{ $service->provider->user->name }}</span>
                            <span class="price">PKR {{ number_format($service->price) }}</span>
                        </div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
                            <a class="btn brand" href="{{ route('services.show', $service->slug) }}">View details</a>
                            <a class="btn secondary" href="{{ route('providers.show', $service->provider->id) }}">Provider</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card">
                    <h3>No services found</h3>
                    <p>Search, category, ya location change karke dobara try karein.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.location-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.dataset.target);
            if (!target || !button.dataset.location) {
                return;
            }

            target.value = button.dataset.location;
            button.textContent = 'City added';
        });
    });

    document.querySelectorAll('.current-location-button').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.dataset.target);
            var info = document.querySelector('.current-location-text');
            if (!navigator.geolocation || !target || !info) {
                return;
            }

            button.disabled = true;
            button.textContent = 'Detecting...';
            info.textContent = 'Location detect ki ja rahi hai...';

            navigator.geolocation.getCurrentPosition(function (position) {
                var coords = position.coords.latitude.toFixed(5) + ', ' + position.coords.longitude.toFixed(5);
                target.value = coords;
                info.textContent = 'Current location: ' + coords;
                button.textContent = 'Location ready';
            }, function () {
                info.textContent = 'Browser location allow karein ya suggestion select karein.';
                button.disabled = false;
                button.textContent = 'Detect now';
            });
        });
    });
});
</script>
@endsection

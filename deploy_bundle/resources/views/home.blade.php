@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container search-layout">
        <section class="hero-shell home-shell">
            <div class="hero-grid home-grid">
                <div class="home-copy">
                    <span class="kicker">{{ __('site.hero_kicker') }}</span>
                    <h1>{{ __('site.hero_title') }}</h1>
                    <p>{{ __('site.hero_text') }}</p>
                    <div class="hero-actions">
                        <a class="btn brand" href="#services">{{ __('site.search_button') }}</a>
                        <a class="btn secondary" href="#providers">{{ __('site.top_providers_title') }}</a>
                    </div>
                    <div class="stats">
                        <div class="stat"><span>Live Services</span><strong>{{ $stats['services'] }}</strong></div>
                        <div class="stat"><span>Verified Providers</span><strong>{{ $stats['providers'] }}</strong></div>
                        <div class="stat"><span>Bookings</span><strong>{{ $stats['bookings'] }}</strong></div>
                        <div class="stat"><span>Reviews</span><strong>{{ $stats['reviews'] }}</strong></div>
                    </div>
                </div>
                <div class="search-shell home-search-shell">
                    <div class="search-panel">
                        <h3 style="margin-top:0;">{{ __('site.search_title') }}</h3>
                        <form method="GET" action="{{ route('home') }}#services">
                            <datalist id="location-suggestions-home">
                                @foreach($locationSuggestions as $suggestion)
                                    <option value="{{ $suggestion }}"></option>
                                @endforeach
                            </datalist>
                            <div class="hero-search-grid">
                                <label style="margin-bottom:0;">{{ __('site.search_need') }}
                                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('site.search_need_placeholder') }}">
                                </label>
                                <label style="margin-bottom:0;">{{ __('site.search_location') }}
                                    <div class="location-input">
                                        <input type="text" name="location" value="{{ $filters['location'] ?? '' }}" list="location-suggestions-home" placeholder="{{ __('site.search_location_placeholder') }}">
                                        @auth
                                            <button class="btn secondary location-trigger" type="button" data-target="input[name=&quot;location&quot;]" data-location="{{ auth()->user()->city }}">{{ __('site.search_saved_city') }}</button>
                                        @endauth
                                    </div>
                                </label>
                                <label style="margin-bottom:0;">{{ __('site.search_category') }}
                                    <select name="category">
                                        <option value="">{{ __('site.search_all_categories') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div style="display:flex;align-items:end;">
                                    <button class="btn brand" type="submit" style="width:100%;">{{ __('site.search_button') }}</button>
                                </div>
                            </div>
                        </form>
                        <div class="hero-search-note">
                            <span>{{ __('site.search_fast_booking') }}</span>
                            <span>{{ __('site.search_provider_dashboard') }}</span>
                            <span>{{ __('site.search_ratings') }}</span>
                            <span>{{ __('site.search_location_feature') }}</span>
                        </div>
                    </div>
                    <div class="card">
                        <div class="location-row">
                            <strong>{{ __('site.current_location') }}</strong>
                            <button class="btn secondary current-location-button" type="button" data-target="input[name=&quot;location&quot;]">{{ __('site.detect_now') }}</button>
                        </div>
                        <p class="muted current-location-text" style="margin:10px 0 0;">
                            @auth
                                {{ __('site.saved_city') }}: {{ auth()->user()->city }}
                            @else
                                {{ __('site.allow_location') }}
                            @endauth
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="categories">
            <div class="section-head">
                <div>
                    <h2>{{ __('site.home_categories_title') }}</h2>
                    <p>{{ __('site.home_categories_text') }}</p>
                </div>
            </div>
            <div class="grid grid-4">
                @foreach($categories as $category)
                    <a class="card icon-card" href="{{ route('home', ['category' => $category->slug]) }}#services">
                        <span class="badge">{{ $category->services_count }} {{ __('site.services_count') }}</span>
                        <h3>{{ $category->name }}</h3>
                        <p>{{ $category->description }}</p>
                        <span class="btn secondary">{{ __('site.explore_category') }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section" id="services">
            <div class="section-head">
                <div>
                    <h2>{{ __('site.featured_services_title') }}</h2>
                    <p>{{ __('site.featured_services_text') }}</p>
                </div>
                @if(($filters['search'] ?? null) || ($filters['location'] ?? null) || ($filters['category'] ?? null))
                    <a class="btn secondary" href="{{ route('home') }}#services">Clear filters</a>
                @endif
            </div>
            <div class="grid grid-3">
                @forelse($featuredServices as $service)
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
                            <div class="notice">{{ $service->provider->service_area }} - {{ $service->provider->user->city }}</div>
                            <div class="provider-strip">
                                <span class="avatar-pill">{{ $service->provider->user->name }}</span>
                                <span class="price">PKR {{ number_format($service->price) }}</span>
                            </div>
                            <div class="stack-actions" style="margin-top:16px;">
                                @if(auth()->check() && auth()->user()->isCustomer())
                                    <a class="btn brand" href="{{ route('bookings.create', $service->slug) }}">Book now</a>
                                @else
                                    <a class="btn brand" href="{{ route('login') }}">Login to book</a>
                                @endif
                                <a class="btn secondary" href="tel:{{ preg_replace('/\D+/', '', $service->provider->user->phone) }}">Call</a>
                                <a class="btn secondary" href="https://wa.me/{{ preg_replace('/\D+/', '', $service->provider->user->phone) }}" target="_blank" rel="noopener">WhatsApp</a>
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
        </section>

        <section class="section" id="providers">
            <div class="section-head">
                <div>
                    <h2>{{ __('site.top_providers_title') }}</h2>
                    <p>{{ __('site.top_providers_text') }}</p>
                </div>
            </div>
            <div class="grid grid-3">
                @forelse($featuredProviders as $provider)
                    <div class="card">
                        <span class="badge">{{ $provider->service_area }}</span>
                        <h3>{{ $provider->user->name }}</h3>
                        <p>{{ $provider->bio }}</p>
                        <div class="notice">{{ $provider->user->city }} - {{ $provider->experience_years }} {{ __('site.years_experience') }} - PKR {{ number_format($provider->hourly_rate) }}/hr</div>
                        <div class="stack-actions" style="margin-top:16px;">
                            <a class="btn secondary" href="tel:{{ preg_replace('/\D+/', '', $provider->user->phone) }}">Call</a>
                            <a class="btn brand" href="https://wa.me/{{ preg_replace('/\D+/', '', $provider->user->phone) }}" target="_blank" rel="noopener">WhatsApp</a>
                        </div>
                    </div>
                @empty
                    <div class="card">
                        <h3>No providers found</h3>
                        <p>Location ya category filters change karke dobara try karein.</p>
                    </div>
                @endforelse
            </div>
        </section>
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
            button.textContent = @json(__('site.city_added'));
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
            button.textContent = @json(__('site.detecting'));
            info.textContent = @json(__('site.detecting'));

            navigator.geolocation.getCurrentPosition(function (position) {
                var coords = position.coords.latitude.toFixed(5) + ', ' + position.coords.longitude.toFixed(5);
                target.value = coords;
                info.textContent = @json(__('site.current_location_label')) + ': ' + coords;
                button.textContent = @json(__('site.location_ready'));
            }, function () {
                info.textContent = @json(__('site.allow_location'));
                button.disabled = false;
                button.textContent = @json(__('site.detect_now'));
            });
        });
    });
});
</script>
@endsection
@extends('layouts.app')

@section('content')
<div class="form-page">
    <div class="container form-layout">
        <div class="form-showcase">
            <span class="kicker">Booking Request</span>
            <h2>{{ $service->title }}</h2>
            <p>{{ $service->short_description }}</p>
            <div class="list" style="margin-top:22px;">
                <div class="notice">Provider: {{ $service->provider->user->name }}</div>
                <div class="notice">Price: PKR {{ number_format($service->price) }}</div>
                <div class="notice">Duration: {{ $service->duration_minutes }} minutes</div>
            </div>
        </div>
        <div class="form-shell">
            <span class="badge">Complete your booking</span>
            <h1>Schedule service</h1>
            <p class="muted">Correct date, address, aur payment method select karein taa ke provider ko clear booking request mile.</p>
            <form method="POST" action="{{ route('bookings.store', $service->slug) }}">
                @csrf
                <div class="form-section">
                    <div class="form-section-title">Appointment details</div>
                    <div class="grid grid-2">
                        <label>Scheduled date and time<input type="datetime-local" name="scheduled_at" required></label>
                        <label>Payment method<select name="payment_method"><option>Cash on Service</option><option>EasyPaisa</option><option>JazzCash</option></select></label>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title">Location</div>
                    <label>Service address<input type="text" name="address" value="{{ old('address', auth()->user()->address) }}" placeholder="Complete service address" required></label>
                </div>
                <div class="form-section">
                    <div class="form-section-title">Extra notes</div>
                    <label>Notes<textarea name="notes" placeholder="Any issue details, floor number, timing notes">{{ old('notes') }}</textarea></label>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button class="btn brand" type="submit">Confirm booking</button>
                    <a class="btn secondary" href="{{ route('services.show', $service->slug) }}">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

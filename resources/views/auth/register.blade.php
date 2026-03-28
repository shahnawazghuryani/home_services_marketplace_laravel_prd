@extends('layouts.app')

@section('content')
<div class="auth-shell container">
    <div class="auth-grid">
        <div class="auth-showcase">
            <span class="kicker">Create Account</span>
            <h2>Professional onboarding for customer aur provider dono.</h2>
            <p>Is form ko clean sections me arrange kiya gaya hai taa ke user easily samajh sake ke personal details aur provider details kahan fill karni hain.</p>
            <div class="list" style="margin-top:22px;">
                <div class="notice">Customer accounts service search aur booking ke liye best hain.</div>
                <div class="notice">Provider accounts service listing, booking management, aur admin approval workflow ke liye hain.</div>
                <div class="notice">Simple, clear, aur mobile-friendly form layout.</div>
            </div>
        </div>
        <div class="auth-card">
            <span class="badge">Registration</span>
            <h1>Create your account</h1>
            <p class="muted">Customer ya provider account create karne ke liye neeche form fill karein.</p>
            <form method="POST" action="{{ route('register.store') }}">
                @csrf
                <div class="form-section">
                    <div class="form-section-title">Basic information</div>
                    <div class="grid grid-2">
                        <label>Full name<input type="text" name="name" value="{{ old('name') }}" placeholder="Enter full name" required></label>
                        <label>Email<input type="email" name="email" value="{{ old('email') }}" placeholder="Enter email" required></label>
                        <label>Phone<input type="text" name="phone" value="{{ old('phone') }}" placeholder="03xx-xxxxxxx" required></label>
                        <label>Role<select name="role"><option value="customer">Customer</option><option value="provider">Service Provider</option></select></label>
                        <label>City<input type="text" name="city" value="{{ old('city') }}" placeholder="Karachi" required></label>
                        <label>Address<input type="text" name="address" value="{{ old('address') }}" placeholder="Street, area, city" required></label>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title">Security</div>
                    <div class="grid grid-2">
                        <label>Password<input type="password" name="password" placeholder="Create password" required></label>
                        <label>Confirm password<input type="password" name="password_confirmation" placeholder="Repeat password" required></label>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title">Provider details</div>
                    <div class="grid grid-2">
                        <label>Service area<input type="text" name="service_area" value="{{ old('service_area') }}" placeholder="DHA, Gulshan, Clifton"></label>
                        <label>Experience years<input type="number" name="experience_years" value="{{ old('experience_years', 0) }}"></label>
                        <label>Hourly rate<input type="number" name="hourly_rate" value="{{ old('hourly_rate', 0) }}"></label>
                        <label>Availability<input type="text" name="availability" value="{{ old('availability') }}" placeholder="Mon-Sat, 10 AM - 7 PM"></label>
                    </div>
                    <label>Provider bio<textarea name="bio" placeholder="Tell users about your experience and working style">{{ old('bio') }}</textarea></label>
                </div>
                <button class="btn brand" type="submit">Create account</button>
            </form>
        </div>
    </div>
</div>
@endsection

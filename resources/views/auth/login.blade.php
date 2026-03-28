@extends('layouts.app')

@section('content')
<div class="auth-shell container">
    <div class="auth-grid">
        <div class="auth-showcase">
            <span class="kicker">Quick Access</span>
            <h2>Login karke apna dashboard instantly open karein.</h2>
            <p>Admin approvals, provider service management, customer bookings, aur reviews sab ek hi clean workflow ke andar available hain.</p>
            <div class="list" style="margin-top:22px;">
                <div class="notice">Customer login se booking history aur reviews manage karein.</div>
                <div class="notice">Provider login se services aur incoming bookings control karein.</div>
                <div class="notice">Admin login se approvals, services, aur platform monitoring handle karein.</div>
            </div>
        </div>

        <div class="auth-card">
            <span class="badge">Secure Login</span>
            <h1 style="margin-bottom:10px;">Welcome back</h1>
            <p class="muted">Apna email aur password enter karein. Agar aap naya account banana chahte hain to register se provider ya customer profile create kar sakte hain.</p>
            <form method="POST" action="{{ route('login.store') }}" style="margin-top:18px;">
                @csrf
                <label>Email
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required>
                </label>
                <label>Password
                    <input type="password" name="password" placeholder="Enter your password" required>
                </label>
                <button class="btn brand" type="submit">Login Now</button>
            </form>
            <div class="notice" style="margin-top:18px;">
                New here? <a href="{{ route('register') }}" style="color: var(--brand-strong); font-weight: 800;">Create account</a>
            </div>
        </div>
    </div>
</div>
@endsection

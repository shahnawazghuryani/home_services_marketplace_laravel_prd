@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container" style="display:grid;gap:24px;max-width:960px;">
        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <span class="kicker">Contact & Help</span>
                    <h1>Need help with booking, provider approval, or account access?</h1>
                    <p class="muted">Hamari support team launch users ko directly assist karti hai. Fastest response WhatsApp aur email par milta hai.</p>
                </div>
            </div>
            <div class="grid grid-3">
                <div class="card">
                    <h3>Email Support</h3>
                    <p class="muted">{{ $supportContact['email'] }}</p>
                    <a class="btn brand" href="mailto:{{ $supportContact['email'] }}">Email us</a>
                </div>
                <div class="card">
                    <h3>Phone Support</h3>
                    <p class="muted">{{ $supportContact['phone'] }}</p>
                    <a class="btn secondary" href="tel:{{ preg_replace('/\D+/', '', $supportContact['phone']) }}">Call now</a>
                </div>
                <div class="card">
                    <h3>WhatsApp Help</h3>
                    <p class="muted">{{ $supportContact['hours'] }}</p>
                    <a class="btn brand" target="_blank" rel="noopener" href="https://wa.me/{{ $supportContact['whatsapp'] }}">Open WhatsApp</a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

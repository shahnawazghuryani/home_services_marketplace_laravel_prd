@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container" style="display:grid;gap:24px;max-width:960px;">
        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <span class="kicker">Terms & Conditions</span>
                    <h1>Platform use ke basic rules</h1>
                </div>
            </div>
            <div class="card" style="display:grid;gap:16px;">
                <p>GharKaam ek marketplace platform hai jahan customers service requests raise karte hain aur providers apni services list karte hain.</p>
                <p>Providers ko accurate profile, lawful listings, aur respectful conduct maintain karna hoga. Admin low-quality, misleading, ya policy-violating content remove kar sakta hai.</p>
                <p>Customers ko booking details, address, timing, aur feedback sachai ke saath submit karna hoga. Fake, abusive, ya harmful usage allowed nahi hai.</p>
                <p>Platform operational moderation, approvals, and service visibility controls reserve karta hai. Help ke liye <a href="{{ route('contact') }}">support page</a> use karein.</p>
            </div>
        </section>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container" style="display:grid;gap:24px;max-width:960px;">
        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <span class="kicker">Privacy Policy</span>
                    <h1>Customer aur provider data ko kis tarah handle kiya jata hai</h1>
                </div>
            </div>
            <div class="card" style="display:grid;gap:16px;">
                <p>GharKaam booking, account, aur support related data collect karta hai taa ke services ko operate, verify, aur improve kiya ja sake.</p>
                <p>Hum user profile details, booking details, traffic analytics, aur support communications store kar sakte hain. Sensitive passwords encrypted storage ke through handle kiye jate hain.</p>
                <p>Operational needs ke liye provider aur customer contact data relevant booking flows me visible ho sakta hai. Hum user data ko spam, abuse, ya policy-violating listings detect karne ke liye review bhi kar sakte hain.</p>
                <p>Agar aap data correction ya account support chahte hain to <a href="mailto:{{ $supportContact['email'] }}">{{ $supportContact['email'] }}</a> par rabta karein.</p>
            </div>
        </section>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container" style="display:grid;gap:24px;max-width:960px;">
        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <span class="kicker">Provider Onboarding SOP</span>
                    <h1>Launch providers ko onboard karne ka practical checklist</h1>
                </div>
            </div>
            <div class="card" style="display:grid;gap:16px;">
                <div class="notice">Step 1: CNIC / phone / service area verify karein.</div>
                <div class="notice">Step 2: Provider bio aur profile photo ko public quality standard ke hisaab se review karein.</div>
                <div class="notice">Step 3: Minimum 2 strong service listings approve hone se pehle complete karwaein.</div>
                <div class="notice">Step 4: Pricing, response time, aur working hours confirm karein.</div>
                <div class="notice">Step 5: WhatsApp aur call responsiveness ek quick test se validate karein.</div>
                <div class="notice">Step 6: Sirf verified providers ko featured ya public launch buckets mein daalein.</div>
            </div>
        </section>
    </div>
</div>
@endsection

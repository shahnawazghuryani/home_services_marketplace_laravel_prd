@extends('layouts.app')

@section('content')
<div class="form-page">
    <div class="container form-layout">
        <div class="form-showcase">
            <span class="kicker">Provider Profile</span>
            <h2>Apni profile ko professional banayein taa ke users zyada trust karein.</h2>
            <p>Yahan se aap personal details, service area, availability, aur experience information update kar sakte hain.</p>
            <div class="list" style="margin-top:22px;">
                <div class="notice">Strong provider profile se admin approval aur customer trust dono better hote hain.</div>
                <div class="notice">Clear bio aur service area se booking conversion improve hoti hai.</div>
                <div class="notice">Updated contact aur availability information hamesha helpful hoti hai.</div>
            </div>
        </div>
        <div class="form-shell">
            <span class="badge">Edit profile</span>
            <h1>Provider profile settings</h1>
            <p class="muted">Neeche apni public profile aur operational details update karein.</p>
            <form method="POST" action="{{ route('provider.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="form-section">
                    <div class="form-section-title">Personal details</div>
                    <div class="grid grid-2">
                        <label>Full name<input type="text" name="name" value="{{ old('name', $user->name) }}" required></label>
                        <label>Phone<input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required></label>
                        <label>City<input type="text" name="city" value="{{ old('city', $user->city) }}" required></label>
                        <label>Address<input type="text" name="address" value="{{ old('address', $user->address) }}" required></label>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title">Professional details</div>
                    <div class="grid grid-2">
                        <label>Experience years<input type="number" name="experience_years" min="0" value="{{ old('experience_years', $profile->experience_years) }}" required></label>
                        <label>Hourly rate<input type="number" name="hourly_rate" min="0" value="{{ old('hourly_rate', $profile->hourly_rate) }}" required></label>
                        <label>Service area<input type="text" name="service_area" value="{{ old('service_area', $profile->service_area) }}" required></label>
                        <label>Availability<input type="text" name="availability" value="{{ old('availability', $profile->availability) }}" required></label>
                    </div>
                    <label>Bio<textarea name="bio" required>{{ old('bio', $profile->bio) }}</textarea></label>
                </div>
                <div class="stack-actions">
                    <button class="btn brand" type="submit">Save profile</button>
                    <a class="btn secondary" href="{{ route('dashboard') }}">Back to dashboard</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

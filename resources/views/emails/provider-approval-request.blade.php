<div style="font-family:Arial,Helvetica,sans-serif;background:#0d0f12;color:#f5f7fa;padding:24px;">
    <div style="max-width:680px;margin:0 auto;background:#17191c;border:1px solid rgba(255,255,255,0.08);border-radius:18px;padding:24px;">
        <h2 style="margin:0 0 16px;">New provider approval request</h2>

        <p style="margin:0 0 12px;">A new provider account was created and is waiting for review.</p>

        <div style="margin:0 0 18px;padding:16px;border-radius:14px;background:#101215;border:1px solid rgba(255,255,255,0.08);">
            <div><strong>Name:</strong> {{ $user->name }}</div>
            <div><strong>Email:</strong> {{ $user->email }}</div>
            <div><strong>Phone:</strong> {{ $user->phone }}</div>
            <div><strong>City:</strong> {{ $user->city }}</div>
            <div><strong>Service area:</strong> {{ $provider->service_area }}</div>
            <div><strong>Availability:</strong> {{ $provider->availability }}</div>
            <div><strong>Experience:</strong> {{ $provider->experience_years }} years</div>
            <div><strong>Hourly rate:</strong> PKR {{ number_format((float) $provider->hourly_rate, 0) }}</div>
            <div style="margin-top:10px;"><strong>Bio:</strong><br>{{ $provider->bio }}</div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;">
            <a href="{{ $approveUrl }}" style="display:inline-block;padding:12px 18px;border-radius:999px;background:#f4b400;color:#111;text-decoration:none;font-weight:700;">Activate provider</a>
            <a href="{{ $deactivateUrl }}" style="display:inline-block;padding:12px 18px;border-radius:999px;background:#2a2d31;color:#f5f7fa;text-decoration:none;font-weight:700;border:1px solid rgba(255,255,255,0.08);">Deactivate provider</a>
        </div>

        <p style="margin:18px 0 0;color:#c2c8d0;font-size:14px;">These links work without login and expire in 7 days.</p>
    </div>
</div>

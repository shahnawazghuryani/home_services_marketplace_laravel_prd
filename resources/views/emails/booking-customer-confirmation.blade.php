<h2>Booking confirmed</h2>
<p>Your booking request for <strong>{{ $service->title }}</strong> has been submitted successfully.</p>
<p>Scheduled time: {{ optional($booking->scheduled_at)->format('d M Y, h:i A') }}</p>
<p>Provider: {{ $provider->name }}</p>
<p>Need help? Email {{ $support['email'] }} or WhatsApp {{ $support['whatsapp'] }}.</p>

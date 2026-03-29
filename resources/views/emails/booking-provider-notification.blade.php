<h2>New booking request</h2>
<p>A customer has requested <strong>{{ $service->title }}</strong>.</p>
<p>Customer: {{ $customer->name }}</p>
<p>Scheduled time: {{ optional($booking->scheduled_at)->format('d M Y, h:i A') }}</p>
<p>For operational help contact {{ $support['email'] }}.</p>

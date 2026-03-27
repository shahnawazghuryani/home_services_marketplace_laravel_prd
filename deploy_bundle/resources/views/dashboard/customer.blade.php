@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container" style="display:grid;gap:24px;">
        <section class="dashboard-hero">
            <div class="dashboard-hero-inner">
                <div>
                    <span class="kicker">Customer Workspace</span>
                    <h1>Track bookings, discover services, aur apne jobs ko confidently manage karein.</h1>
                    <p>Yahan se aap apne active bookings monitor kar sakte hain, completed work par review submit kar sakte hain, aur site usage bhi dekh sakte hain.</p>
                    <div class="dashboard-stats">
                        <div class="dashboard-stat"><span>Total bookings</span><strong>{{ $bookings->count() }}</strong></div>
                        <div class="dashboard-stat"><span>Completed</span><strong>{{ $bookings->where('status', 'completed')->count() }}</strong></div>
                        <div class="dashboard-stat"><span>Pending</span><strong>{{ $bookings->where('status', 'pending')->count() }}</strong></div>
                        <div class="dashboard-stat"><span>Reviews</span><strong>{{ $bookings->filter(fn ($booking) => $booking->reviews->count() > 0)->count() }}</strong></div>
                    </div>
                    <div class="dashboard-stats">
                        <div class="dashboard-stat"><span>Your site visits</span><strong>{{ $trafficSummary['your_visits'] }}</strong></div>
                        <div class="dashboard-stat"><span>Today site visits</span><strong>{{ $trafficSummary['today_site_visits'] }}</strong></div>
                        <div class="dashboard-stat"><span>Top source</span><strong style="font-size:1rem;">{{ $trafficSummary['top_source'] }}</strong></div>
                    </div>
                    <div class="dashboard-actions">
                        <a class="btn brand" href="{{ route('services.index') }}">Find new service</a>
                        <a class="btn secondary" href="{{ route('home') }}">Back to home</a>
                    </div>
                </div>
                <div class="dashboard-side">
                    <h3 style="margin-top:0;">Notifications</h3>
                    <div class="list">
                        @forelse($notifications as $notification)
                            <div class="notice">{{ $notification->title }}<br><span class="muted" style="color:rgba(255,255,255,0.78);">{{ $notification->message }}</span></div>
                        @empty
                            <div class="notice">No notifications yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Browse categories</h3>
                    <p class="muted">Quick access for common household needs.</p>
                </div>
            </div>
            <div class="grid grid-3">
                @foreach($categories as $category)
                    <a class="quick-item" href="{{ route('services.index', ['category' => $category->slug]) }}">
                        <div>
                            <strong>{{ $category->name }}</strong>
                            <div class="muted">{{ $category->services_count }} services available</div>
                        </div>
                        <span class="badge">Open</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Your bookings</h3>
                    <p class="muted">See status, cancel pending jobs, aur completed jobs par review dein.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Service</th><th>Provider</th><th>Schedule</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
                    <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->service->title }}</td>
                            <td>{{ $booking->provider->name }}</td>
                            <td>{{ $booking->scheduled_at->format('d M Y, h:i A') }}</td>
                            <td><span class="status {{ $booking->status }}">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            <td class="table-actions">
                                @if($booking->status === 'pending')
                                    <form method="POST" action="{{ route('bookings.status', $booking) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="btn danger" type="submit">Cancel booking</button>
                                    </form>
                                @elseif($booking->status === 'completed')
                                    <form method="POST" action="{{ route('bookings.reviews.store', $booking) }}">
                                        @csrf
                                        <div class="grid" style="gap:10px;">
                                            <input type="number" min="1" max="5" name="rating" placeholder="Rating 1 to 5" required>
                                            <textarea name="comment" placeholder="Share your feedback" required></textarea>
                                            <button class="btn success" type="submit">Submit review</button>
                                        </div>
                                    </form>
                                @else
                                    <span class="muted">No action needed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No bookings yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
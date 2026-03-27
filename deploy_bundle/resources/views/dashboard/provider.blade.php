@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container" style="display:grid;gap:24px;">
        <section class="dashboard-hero">
            <div class="dashboard-hero-inner">
                <div>
                    <span class="kicker">Provider Workspace</span>
                    <h1>Services manage karein, bookings handle karein, aur profile ko professionally present karein.</h1>
                    <p>Provider dashboard ko is tarah redesign kiya gaya hai ke service management, booking handling, aur profile traffic sab fast aur clear lagen.</p>
                    <div class="dashboard-stats">
                        <div class="dashboard-stat"><span>Services</span><strong>{{ $services->count() }}</strong></div>
                        <div class="dashboard-stat"><span>Bookings</span><strong>{{ $bookings->count() }}</strong></div>
                        <div class="dashboard-stat"><span>Approved</span><strong>{{ $profile?->approved_at ? 'Yes' : 'No' }}</strong></div>
                        <div class="dashboard-stat"><span>Area</span><strong style="font-size:1rem;">{{ $profile?->service_area }}</strong></div>
                    </div>
                    <div class="dashboard-stats">
                        <div class="dashboard-stat"><span>Total profile views</span><strong>{{ $trafficSummary['total_views'] }}</strong></div>
                        <div class="dashboard-stat"><span>Today views</span><strong>{{ $trafficSummary['today_views'] }}</strong></div>
                    </div>
                    <div class="dashboard-actions">
                        <a class="btn secondary" href="{{ route('provider.profile.edit') }}">Edit profile</a>
                        @if($profile?->approved_at)
                            <a class="btn brand" href="{{ route('provider.services.create') }}">Add new service</a>
                        @endif
                    </div>
                </div>
                <div class="dashboard-side">
                    <h3 style="margin-top:0;">Profile summary</h3>
                    <div class="list">
                        <div class="notice">Approval status: {{ $profile?->approved_at ? 'Approved and visible to customers' : 'Pending admin review' }}</div>
                        <div class="notice">Availability: {{ $profile?->availability }}</div>
                        <div class="notice">Hourly rate: PKR {{ number_format($profile?->hourly_rate ?? 0) }}</div>
                    </div>
                    @if(! $profile?->approved_at)
                        <div class="notice" style="margin-top:14px;">Admin approval ke baad hi nayi services add ki ja sakti hain.</div>
                    @endif
                </div>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Traffic sources</h3>
                    <p class="muted">Dekhein aapki profile aur services kis source se dekhi gayi hain.</p>
                </div>
            </div>
            <div class="grid grid-3">
                @forelse($trafficSummary['top_sources'] as $source)
                    <div class="card">
                        <h3 style="margin-top:0;">{{ $source->source ?? 'Direct' }}</h3>
                        <p class="muted">{{ $source->total }} view(s)</p>
                    </div>
                @empty
                    <div class="notice">Abhi tak koi traffic data available nahi hai.</div>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Notifications</h3>
                    <p class="muted">Latest platform and customer activity.</p>
                </div>
            </div>
            <div class="grid grid-3">
                @forelse($notifications as $notification)
                    <div class="quick-item">
                        <div>
                            <strong>{{ $notification->title }}</strong>
                            <div class="muted">{{ $notification->message }}</div>
                        </div>
                    </div>
                @empty
                    <div class="notice">No notifications yet.</div>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Your services</h3>
                    <p class="muted">Filter, edit, ya delete your service catalog from one place.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-2" style="margin-bottom:18px;">
                <label>Search service<input type="text" name="service_search" value="{{ request('service_search') }}" placeholder="title ya short description"></label>
                <label>Status
                    <select name="service_status">
                        <option value="">All</option>
                        <option value="active" @selected(request('service_status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('service_status') === 'inactive')>Inactive</option>
                    </select>
                </label>
                <div class="stack-actions">
                    <button class="btn brand" type="submit">Apply filter</button>
                    <a class="btn secondary" href="{{ route('dashboard') }}">Reset</a>
                </div>
            </form>
            <div class="grid grid-2">
                @forelse($services as $service)
                    <div class="quick-item">
                        <div>
                            <strong>{{ $service->title }}</strong>
                            <div class="muted">{{ $service->category->name }} - PKR {{ number_format($service->price) }} - {{ $service->duration_minutes }} mins - {{ $service->is_active ? 'Active' : 'Inactive' }}</div>
                        </div>
                        <div class="stack-actions">
                            <a class="btn secondary" href="{{ route('provider.services.edit', $service) }}">Edit</a>
                            <form method="POST" action="{{ route('provider.services.destroy', $service) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="notice">Abhi koi service add nahi ki gayi.</div>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Incoming bookings</h3>
                    <p class="muted">Customer requests ko accept, reject, ya complete karein.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Customer</th><th>Service</th><th>Schedule</th><th>Status</th><th class="table-actions">Update</th></tr></thead>
                    <tbody>
                    @foreach($bookings as $booking)
                        <tr>
                            <td>{{ $booking->customer->name }}</td>
                            <td>{{ $booking->service->title }}</td>
                            <td>{{ $booking->scheduled_at->format('d M Y, h:i A') }}</td>
                            <td><span class="status {{ $booking->status }}">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            <td class="table-actions">
                                <form method="POST" action="{{ route('bookings.status', $booking) }}">
                                    @csrf
                                    <div class="grid" style="gap:10px;">
                                        <select name="status">
                                            <option value="accepted">Accept</option>
                                            <option value="rejected">Reject</option>
                                            <option value="in_progress">In progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                        <button class="btn brand" type="submit">Save update</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container" style="display:grid;gap:24px;">
        <section class="dashboard-hero">
            <div class="dashboard-hero-inner">
                <div>
                    <span class="kicker">Admin Control Center</span>
                    <h1>Platform ko professionally monitor aur manage karein.</h1>
                    <p>Admin dashboard ko stats-first aur operations-friendly style me redesign kiya gaya hai taa ke approvals, categories, services, bookings, aur traffic sab easily handle ho sakein.</p>
                    <div class="dashboard-stats">
                        <div class="dashboard-stat"><span>Users</span><strong>{{ $stats['users'] }}</strong></div>
                        <div class="dashboard-stat"><span>Providers</span><strong>{{ $stats['providers'] }}</strong></div>
                        <div class="dashboard-stat"><span>Bookings</span><strong>{{ $stats['bookings'] }}</strong></div>
                        <div class="dashboard-stat"><span>Revenue</span><strong>PKR {{ number_format($stats['revenue']) }}</strong></div>
                    </div>
                    <div class="dashboard-stats">
                        <div class="dashboard-stat"><span>Total visits</span><strong>{{ $stats['visits'] }}</strong></div>
                        <div class="dashboard-stat"><span>Unique visitors</span><strong>{{ $stats['unique_visitors'] }}</strong></div>
                        <div class="dashboard-stat"><span>Today visits</span><strong>{{ $trafficSummary['today'] }}</strong></div>
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
                    <h3>Website traffic</h3>
                    <p class="muted">Dekhein website kitni baar khuli aur users kahan se aaye.</p>
                </div>
            </div>
            <div class="grid grid-3">
                <div class="card">
                    <h3>Top sources</h3>
                    <div class="list">
                        @forelse($trafficSummary['top_sources'] as $source)
                            <div class="notice">{{ $source->source ?? 'Direct' }} - {{ $source->total }} visits</div>
                        @empty
                            <div class="notice">No traffic data yet.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <h3>Top pages</h3>
                    <div class="list">
                        @forelse($trafficSummary['top_pages'] as $page)
                            <div class="notice">/{{ $page->path }} - {{ $page->total }} visits</div>
                        @empty
                            <div class="notice">No page visits yet.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <h3>Recent visits</h3>
                    <div class="list">
                        @forelse($trafficSummary['latest_visits'] as $visit)
                            <div class="notice">{{ $visit->source }} - /{{ $visit->path }} - {{ $visit->device_type }}<br><span class="muted">{{ $visit->visited_at->format('d M Y, h:i A') }}</span></div>
                        @empty
                            <div class="notice">No visit log yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Category management</h3>
                    <p class="muted">New service categories add karein aur existing categories maintain karein.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="grid grid-2" style="margin-bottom:18px;">
                @csrf
                <label>Name<input type="text" name="name" placeholder="Category name" required></label>
                <label>Icon<input type="text" name="icon" placeholder="Optional icon"></label>
                <label style="grid-column:1 / -1;">Description<textarea name="description" placeholder="Short category description"></textarea></label>
                <div><button class="btn brand" type="submit">Add category</button></div>
            </form>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Name</th><th>Description</th><th class="table-actions">Actions</th></tr></thead>
                    <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->description }}</td>
                            <td class="table-actions">
                                <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid" style="gap:10px;">
                                        <input type="text" name="name" value="{{ $category->name }}">
                                        <input type="text" name="icon" value="{{ $category->icon }}">
                                        <textarea name="description">{{ $category->description }}</textarea>
                                        <div class="stack-actions">
                                            <button class="btn secondary" type="submit">Update</button>
                                        </div>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="margin-top:10px;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Provider approvals</h3>
                    <p class="muted">Review live providers and switch approval states.</p>
                </div>
            </div>
            <div class="grid grid-2">
                @foreach($providers as $provider)
                    <div class="quick-item">
                        <div>
                            <strong>{{ $provider->user->name }}</strong>
                            <div class="muted">{{ $provider->service_area }} - {{ $provider->approved_at ? 'Approved' : 'Pending approval' }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.providers.approve', $provider) }}">
                            @csrf
                            <button class="btn {{ $provider->approved_at ? 'warning' : 'success' }}" type="submit">{{ $provider->approved_at ? 'Set pending' : 'Approve' }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Service management</h3>
                    <p class="muted">Edit provider services or remove low-quality listings.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Service</th><th>Provider</th><th>Category</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
                    <tbody>
                    @foreach($services as $service)
                        <tr>
                            <td>{{ $service->title }}</td>
                            <td>{{ $service->provider->user->name }}</td>
                            <td>{{ $service->category->name }}</td>
                            <td><span class="status {{ $service->is_active ? 'accepted' : 'pending' }}">{{ $service->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="table-actions">
                                <div class="stack-actions">
                                    <a class="btn secondary" href="{{ route('admin.services.edit', $service) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.services.destroy', $service) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-title">
                <div>
                    <h3>Recent bookings</h3>
                    <p class="muted">Latest activity across the marketplace.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Customer</th><th>Provider</th><th>Service</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($bookings as $booking)
                        <tr>
                            <td>{{ $booking->customer->name }}</td>
                            <td>{{ $booking->provider->name }}</td>
                            <td>{{ $booking->service->title }}</td>
                            <td><span class="status {{ $booking->status }}">{{ $booking->status }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
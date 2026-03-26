<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Service;
use App\Support\MarketplaceNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function create(string $slug)
    {
        $service = Service::with(['category', 'provider.user'])->where('slug', $slug)->firstOrFail();

        abort_unless(auth()->user()?->isCustomer(), 403);

        return view('bookings.create', compact('service'));
    }

    public function store(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->isCustomer(), 403);

        $service = Service::with(['provider.user'])->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'string', 'max:100'],
        ]);

        $booking = Booking::create([
            'customer_id' => $request->user()->id,
            'provider_id' => $service->provider->user_id,
            'service_id' => $service->id,
            'scheduled_at' => $data['scheduled_at'],
            'address' => $data['address'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'total_amount' => $service->price,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'customer_id' => $request->user()->id,
            'amount' => $service->price,
            'method' => $data['payment_method'],
            'status' => $data['payment_method'] === 'Cash on Service' ? 'pending' : 'initiated',
            'transaction_reference' => 'PAY-' . strtoupper(Str::random(8)),
        ]);

        MarketplaceNotifier::send($service->provider->user_id, 'New booking request', 'A customer has requested ' . $service->title . '.', 'info', '/dashboard');
        MarketplaceNotifier::send($request->user()->id, 'Booking submitted', 'Your booking request has been sent to the provider.', 'success', '/dashboard');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Booking request submitted successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Booking request submitted successfully.');
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:accepted,rejected,in_progress,completed,cancelled'],
        ]);

        $user = $request->user();
        $canManage = $user->isAdmin()
            || ($user->isProvider() && $booking->provider_id === $user->id)
            || ($user->isCustomer() && $booking->customer_id === $user->id && $request->input('status') === 'cancelled');

        abort_unless($canManage, 403);

        $booking->update(['status' => $request->input('status')]);

        if ($booking->status === 'completed') {
            $payment = Payment::where('booking_id', $booking->id)->first();
            if ($payment) {
                $payment->update(['status' => 'paid', 'paid_at' => now()]);
            }
        }

        MarketplaceNotifier::send($booking->customer_id, 'Booking updated', 'Your booking for ' . $booking->service->title . ' is now ' . str_replace('_', ' ', $booking->status) . '.', 'info', '/dashboard');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Booking status updated.',
            ]);
        }

        return back()->with('success', 'Booking status updated.');
    }

    public function storeReview(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->isCustomer() && $booking->customer_id === $request->user()->id && $booking->status === 'completed', 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:500'],
        ]);

        Review::updateOrCreate(
            ['booking_id' => $booking->id, 'customer_id' => $request->user()->id],
            ['provider_id' => $booking->provider_id, 'rating' => $data['rating'], 'comment' => $data['comment']]
        );

        MarketplaceNotifier::send($booking->provider_id, 'New review received', 'A customer left a review for ' . $booking->service->title . '.', 'success', '/dashboard');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Review submitted successfully.',
            ]);
        }

        return back()->with('success', 'Review submitted successfully.');
    }
}

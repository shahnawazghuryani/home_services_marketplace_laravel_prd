<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingMailService
{
    public function sendCreated(Booking $booking, Service $service, User $customer, User $provider): void
    {
        $support = config('services.support');
        $payload = [
            'booking' => $booking,
            'service' => $service,
            'customer' => $customer,
            'provider' => $provider,
            'support' => $support,
        ];

        $this->sendIfPossible(
            $customer->email,
            'emails.booking-customer-confirmation',
            $payload,
            'Booking confirmation - ' . config('app.name')
        );

        $this->sendIfPossible(
            $provider->email,
            'emails.booking-provider-notification',
            $payload,
            'New booking request - ' . config('app.name')
        );
    }

    private function sendIfPossible(?string $to, string $view, array $data, string $subject): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::send($view, $data, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $exception) {
            Log::error('Booking email dispatch failed.', [
                'recipient' => $to,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

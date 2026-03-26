<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\MarketplaceNotification;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\WebsiteVisit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        Review::query()->delete();
        Payment::query()->delete();
        Booking::query()->delete();
        Service::query()->delete();
        Provider::query()->delete();
        MarketplaceNotification::query()->delete();
        Category::query()->delete();
        WebsiteVisit::query()->delete();
        User::query()->delete();

        $admin = User::create([
            'name' => 'Admin Manager',
            'email' => 'admin@homeservices.test',
            'phone' => '0300-0000000',
            'role' => 'admin',
            'city' => 'Karachi',
            'address' => 'Head Office, Shahrah-e-Faisal',
            'password' => Hash::make('password'),
        ]);

        $customer = User::create([
            'name' => 'Ayesha Khan',
            'email' => 'customer@homeservices.test',
            'phone' => '0301-1111111',
            'role' => 'customer',
            'city' => 'Karachi',
            'address' => 'Block 7, Gulshan-e-Iqbal',
            'password' => Hash::make('password'),
        ]);

        $categories = collect([
            ['name' => 'Plumbing', 'icon' => 'Wrench', 'description' => 'Leak fixes, pipelines, tank fitting, and sanitary work.'],
            ['name' => 'Electrical', 'icon' => 'Zap', 'description' => 'Wiring, power faults, fans, switches, and board maintenance.'],
            ['name' => 'Cleaning', 'icon' => 'Sparkles', 'description' => 'Deep cleaning, sofa cleaning, move-in and move-out jobs.'],
            ['name' => 'AC Repair', 'icon' => 'Snowflake', 'description' => 'Cooling issues, service, gas refill, and installation support.'],
        ])->mapWithKeys(function (array $category) {
            $slug = Str::slug($category['name']);

            return [
                $slug => Category::create([
                    'name' => $category['name'],
                    'slug' => $slug,
                    'icon' => $category['icon'],
                    'description' => $category['description'],
                ]),
            ];
        });

        $providers = collect([
            [
                'user' => ['name' => 'Ali Raza', 'email' => 'provider1@homeservices.test', 'phone' => '0302-2222222', 'city' => 'Karachi', 'address' => 'North Nazimabad'],
                'profile' => ['bio' => 'Certified plumber focused on quick diagnosis and clean finishing.', 'experience_years' => 8, 'hourly_rate' => 2500, 'service_area' => 'Karachi Central', 'availability' => 'Mon-Sat, 9 AM - 8 PM', 'is_featured' => true],
                'services' => [
                    ['category' => 'plumbing', 'title' => 'Emergency Plumbing Visit', 'short_description' => 'Fast response for leakage, blockage, and water flow issues.', 'description' => 'On-site plumbing diagnosis, minor repair, pipe adjustments, and fitting support for kitchens and washrooms.', 'price' => 3500, 'duration_minutes' => 90],
                    ['category' => 'plumbing', 'title' => 'Bathroom Fixture Installation', 'short_description' => 'Install taps, commodes, showers, and accessories.', 'description' => 'Complete fitting support for bathroom fixtures with sealing, testing, and cleanup included.', 'price' => 5000, 'duration_minutes' => 120],
                ],
            ],
            [
                'user' => ['name' => 'Usman Tariq', 'email' => 'provider2@homeservices.test', 'phone' => '0303-3333333', 'city' => 'Karachi', 'address' => 'PECHS'],
                'profile' => ['bio' => 'Trusted electrician for safe residential fixes and maintenance.', 'experience_years' => 6, 'hourly_rate' => 2200, 'service_area' => 'Karachi East', 'availability' => 'Daily, 10 AM - 9 PM', 'is_featured' => true],
                'services' => [
                    ['category' => 'electrical', 'title' => 'Home Electrical Inspection', 'short_description' => 'Diagnose power trips, switches, sockets, and wiring faults.', 'description' => 'Detailed home electrical inspection with safety checks, troubleshooting, and repair recommendations.', 'price' => 2800, 'duration_minutes' => 60],
                    ['category' => 'electrical', 'title' => 'Fan and Light Installation', 'short_description' => 'Install fans, lights, and replace faulty holders.', 'description' => 'Professional fan and light fitting service with testing and secure installation for homes and apartments.', 'price' => 3200, 'duration_minutes' => 75],
                ],
            ],
            [
                'user' => ['name' => 'Sana Javed', 'email' => 'provider3@homeservices.test', 'phone' => '0304-4444444', 'city' => 'Karachi', 'address' => 'Clifton'],
                'profile' => ['bio' => 'Detail-oriented cleaning specialist for premium homes and offices.', 'experience_years' => 5, 'hourly_rate' => 2000, 'service_area' => 'Clifton and DHA', 'availability' => 'Mon-Sun, 8 AM - 6 PM', 'is_featured' => true],
                'services' => [
                    ['category' => 'cleaning', 'title' => 'Deep Home Cleaning', 'short_description' => 'Kitchen, washroom, room, and floor deep cleaning.', 'description' => 'Thorough deep cleaning package with stain attention, sanitization, and room-by-room detailing.', 'price' => 6500, 'duration_minutes' => 180],
                    ['category' => 'ac-repair', 'title' => 'AC Service and Filter Cleaning', 'short_description' => 'Improve cooling with a full service visit.', 'description' => 'Basic AC servicing including filter cleaning, airflow checks, and wash support for split AC units.', 'price' => 4000, 'duration_minutes' => 90],
                ],
            ],
        ])->map(function (array $providerData) use ($categories) {
            $user = User::create([
                ...$providerData['user'],
                'role' => 'provider',
                'password' => Hash::make('password'),
            ]);

            $provider = Provider::create([
                ...$providerData['profile'],
                'user_id' => $user->id,
                'approved_at' => now(),
            ]);

            foreach ($providerData['services'] as $serviceData) {
                Service::create([
                    'provider_id' => $provider->id,
                    'category_id' => $categories[$serviceData['category']]->id,
                    'title' => $serviceData['title'],
                    'slug' => Str::slug($serviceData['title'] . '-' . $user->name),
                    'short_description' => $serviceData['short_description'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'price_type' => 'fixed',
                    'duration_minutes' => $serviceData['duration_minutes'],
                    'is_active' => true,
                ]);
            }

            return $provider;
        });

        $completedService = Service::where('title', 'Emergency Plumbing Visit')->first();
        $completedBooking = Booking::create([
            'customer_id' => $customer->id,
            'provider_id' => $providers->first()->user_id,
            'service_id' => $completedService->id,
            'scheduled_at' => now()->subDays(2),
            'address' => 'Block 7, Gulshan-e-Iqbal, Karachi',
            'notes' => 'Kitchen leakage issue.',
            'status' => 'completed',
            'total_amount' => $completedService->price,
        ]);

        Payment::create([
            'booking_id' => $completedBooking->id,
            'customer_id' => $customer->id,
            'amount' => $completedService->price,
            'method' => 'Cash on Service',
            'status' => 'paid',
            'transaction_reference' => 'PAY-' . strtoupper(Str::random(8)),
            'paid_at' => now()->subDays(2),
        ]);

        Review::create([
            'booking_id' => $completedBooking->id,
            'customer_id' => $customer->id,
            'provider_id' => $providers->first()->user_id,
            'rating' => 5,
            'comment' => 'Very responsive, reached on time and fixed the leakage neatly.',
        ]);

        Booking::create([
            'customer_id' => $customer->id,
            'provider_id' => $providers[1]->user_id,
            'service_id' => Service::where('title', 'Home Electrical Inspection')->first()->id,
            'scheduled_at' => now()->addDay(),
            'address' => 'Gulshan-e-Iqbal, Karachi',
            'notes' => 'Power trip in one room.',
            'status' => 'pending',
            'total_amount' => 2800,
        ]);

        foreach ([$admin, $customer, ...User::where('role', 'provider')->get()->all()] as $user) {
            MarketplaceNotification::create([
                'user_id' => $user->id,
                'title' => 'Welcome to HomeEase',
                'message' => 'Your dashboard is ready with live sample data and booking workflow.',
                'type' => 'success',
                'action_url' => '/dashboard',
                'is_read' => false,
            ]);
        }
    }
}

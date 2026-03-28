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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LocalMarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            Review::query()->delete();
            Payment::query()->delete();
            Booking::query()->delete();
            DB::table('category_service')->delete();
            Service::query()->delete();
            Provider::query()->delete();
            MarketplaceNotification::query()->delete();
            Category::query()->delete();
            WebsiteVisit::query()->delete();
            User::query()->delete();

            $admin = User::create([
                'name' => 'Admin Manager',
                'email' => 'admin@gharkaam.test',
                'phone' => '0300-0000000',
                'role' => 'admin',
                'city' => 'Karachi',
                'address' => 'Head Office, Shahrah-e-Faisal, Karachi',
                'password' => Hash::make('password123'),
            ]);

            $customer = User::create([
                'name' => 'Sara Ahmed',
                'email' => 'sara.customer@gharkaam.test',
                'phone' => '0301-1111111',
                'role' => 'customer',
                'city' => 'Karachi',
                'address' => 'Block 4, Gulshan-e-Iqbal, Karachi',
                'password' => Hash::make('password123'),
            ]);

            $categories = collect([
                ['name' => 'Plumbing', 'icon' => 'Wrench', 'description' => 'Leak fixes, fitting, blockage clearing, and washroom maintenance.'],
                ['name' => 'Cleaning', 'icon' => 'Sparkles', 'description' => 'Home, tank, and move-in deep cleaning support.'],
                ['name' => 'Electrical', 'icon' => 'Zap', 'description' => 'Wiring, switches, lights, and inspection services.'],
            ])->mapWithKeys(function (array $category): array {
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

            $providerUser = User::create([
                'name' => 'Shahnawaz Ali',
                'email' => 'shahnawaz.provider@gharkaam.test',
                'phone' => '0302-2222222',
                'role' => 'provider',
                'city' => 'Karachi',
                'address' => 'DHA Phase 6, Karachi',
                'password' => Hash::make('password123'),
            ]);

            $provider = Provider::create([
                'user_id' => $providerUser->id,
                'bio' => 'Reliable home services provider focused on quick response, clean work, and on-time visits.',
                'experience_years' => 7,
                'hourly_rate' => 2800,
                'service_area' => 'DHA, Clifton, Gulshan, PECHS',
                'availability' => 'Mon-Sat, 9 AM - 8 PM',
                'approved_at' => now(),
                'is_featured' => true,
            ]);

            $services = collect([
                [
                    'category' => 'plumbing',
                    'title' => 'Emergency Plumbing Visit',
                    'short_description' => 'Fast help for leakage, choking, low pressure, and urgent bathroom issues.',
                    'description' => 'On-site plumbing inspection and repair support for pipelines, taps, commodes, sinks, and urgent leakage complaints.',
                    'price' => 3500,
                    'duration_minutes' => 90,
                ],
                [
                    'category' => 'cleaning',
                    'title' => 'Water Tank Cleaning Service',
                    'short_description' => 'Scheduled home water tank cleaning with wash, sludge removal, and sanitization.',
                    'description' => 'Professional overhead and underground water tank cleaning with sludge removal, scrub wash, sanitization, and final rinse.',
                    'price' => 6500,
                    'duration_minutes' => 150,
                ],
            ])->map(function (array $serviceData) use ($categories, $provider, $providerUser): Service {
                $category = $categories[$serviceData['category']];

                $service = Service::create([
                    'provider_id' => $provider->id,
                    'category_id' => $category->id,
                    'title' => $serviceData['title'],
                    'slug' => Str::slug($serviceData['title'] . '-' . $providerUser->name),
                    'short_description' => $serviceData['short_description'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'price_type' => 'fixed',
                    'duration_minutes' => $serviceData['duration_minutes'],
                    'is_active' => true,
                ]);

                $service->categories()->sync([$category->id]);

                return $service;
            });

            $completedBooking = Booking::create([
                'customer_id' => $customer->id,
                'provider_id' => $providerUser->id,
                'service_id' => $services->first()->id,
                'scheduled_at' => now()->subDay(),
                'address' => $customer->address,
                'notes' => 'Kitchen leakage aur low pressure issue.',
                'status' => 'completed',
                'total_amount' => $services->first()->price,
            ]);

            Payment::create([
                'booking_id' => $completedBooking->id,
                'customer_id' => $customer->id,
                'amount' => $services->first()->price,
                'method' => 'Cash on Service',
                'status' => 'paid',
                'transaction_reference' => 'PAY-' . strtoupper(Str::random(8)),
                'paid_at' => now()->subDay(),
            ]);

            Review::create([
                'booking_id' => $completedBooking->id,
                'customer_id' => $customer->id,
                'provider_id' => $providerUser->id,
                'rating' => 5,
                'comment' => 'Shahnawaz time par aya aur issue clean tareeqay se resolve kiya.',
            ]);

            $pendingBooking = Booking::create([
                'customer_id' => $customer->id,
                'provider_id' => $providerUser->id,
                'service_id' => $services->last()->id,
                'scheduled_at' => now()->addDay(),
                'address' => $customer->address,
                'notes' => 'Weekend tank cleaning required.',
                'status' => 'pending',
                'total_amount' => $services->last()->price,
            ]);

            Payment::create([
                'booking_id' => $pendingBooking->id,
                'customer_id' => $customer->id,
                'amount' => $services->last()->price,
                'method' => 'Cash on Service',
                'status' => 'pending',
                'transaction_reference' => 'PAY-' . strtoupper(Str::random(8)),
            ]);

            foreach ([$admin, $customer, $providerUser] as $user) {
                MarketplaceNotification::create([
                    'user_id' => $user->id,
                    'title' => 'Local demo ready',
                    'message' => 'Local marketplace demo data is ready for dashboard, booking, and admin testing.',
                    'type' => 'success',
                    'action_url' => '/dashboard',
                    'is_read' => false,
                ]);
            }
        });
    }
}

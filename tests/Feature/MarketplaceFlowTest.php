<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\WebsiteVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_core_flow_works_for_admin_provider_and_customer(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Manager',
            'email' => 'admin@gharkaam.test',
            'phone' => '03000000000',
            'role' => 'admin',
            'city' => 'Karachi',
            'address' => 'Head Office',
            'password' => 'password123',
        ]);

        $category = Category::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'description' => 'Plumbing services',
        ]);

        $providerRegistration = $this->postJson('/register', [
            'name' => 'Shahnawaz Ali',
            'email' => 'shahnawaz.provider@gharkaam.test',
            'phone' => '03022222222',
            'role' => 'provider',
            'city' => 'Karachi',
            'address' => 'DHA Phase 6',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'bio' => 'Trusted plumbing specialist',
            'experience_years' => 7,
            'hourly_rate' => 2800,
            'service_area' => 'DHA, Clifton',
            'availability' => 'Mon-Sat, 9 AM - 8 PM',
        ]);

        $providerRegistration->assertOk()
            ->assertJsonPath('redirect', route('dashboard'));

        $providerUser = User::where('email', 'shahnawaz.provider@gharkaam.test')->firstOrFail();
        $providerProfile = Provider::where('user_id', $providerUser->id)->firstOrFail();

        $this->assertNull($providerProfile->approved_at);

        $this->postJson('/logout')->assertOk();

        $customerRegistration = $this->postJson('/register', [
            'name' => 'Sara Ahmed',
            'email' => 'sara.customer@gharkaam.test',
            'phone' => '03011111111',
            'role' => 'customer',
            'city' => 'Karachi',
            'address' => 'Block 4, Gulshan-e-Iqbal',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $customerRegistration->assertOk();

        $customer = User::where('email', 'sara.customer@gharkaam.test')->firstOrFail();

        $this->postJson('/logout')->assertOk();

        $this->postJson('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertOk();

        $this->postJson('/admin/providers/' . $providerProfile->id . '/approve')
            ->assertOk()
            ->assertJsonPath('message', 'Provider approval status updated.');

        $this->assertNotNull($providerProfile->fresh()->approved_at);

        WebsiteVisit::create([
            'provider_id' => $providerProfile->id,
            'service_id' => null,
            'user_id' => null,
            'visitor_key' => 'test-visitor',
            'path' => 'providers/' . $providerProfile->id,
            'full_url' => 'http://localhost/providers/' . $providerProfile->id,
            'referrer_url' => null,
            'source' => 'Direct',
            'device_type' => 'desktop',
            'visited_at' => now(),
        ]);

        $adminDashboard = $this->getJson('/dashboard/data');
        $adminDashboard->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonFragment(['name' => 'Shahnawaz Ali'])
            ->assertJsonFragment(['total_views' => 1]);

        $this->postJson('/logout')->assertOk();

        $this->postJson('/login', [
            'email' => $providerUser->email,
            'password' => 'password123',
        ])->assertOk();

        $serviceCreate = $this->postJson('/provider/services', [
            'category_ids' => [$category->id],
            'title' => 'Emergency Plumbing Visit',
            'short_description' => 'Fast help for leakage and urgent bathroom issues.',
            'description' => 'On-site plumbing inspection and urgent repair support.',
            'price' => 3500,
            'price_type' => 'fixed',
            'duration_minutes' => 90,
            'is_active' => true,
        ]);

        $serviceCreate->assertOk()
            ->assertJsonPath('redirect', route('dashboard'));

        $service = Service::where('title', 'Emergency Plumbing Visit')->firstOrFail();

        $providerDashboard = $this->getJson('/dashboard/data');
        $providerDashboard->assertOk()
            ->assertJsonPath('role', 'provider')
            ->assertJsonFragment(['title' => 'Emergency Plumbing Visit']);

        $this->postJson('/logout')->assertOk();

        $this->postJson('/login', [
            'email' => $customer->email,
            'password' => 'password123',
        ])->assertOk();

        $bookingCreateData = $this->getJson('/services/' . $service->slug . '/book/data');
        $bookingCreateData->assertOk()
            ->assertJsonPath('service.title', 'Emergency Plumbing Visit');

        $bookingResponse = $this->postJson('/services/' . $service->slug . '/book', [
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'address' => 'Block 4, Gulshan-e-Iqbal, Karachi',
            'notes' => 'Kitchen sink leakage',
            'payment_method' => 'Cash on Service',
        ]);

        $bookingResponse->assertOk()
            ->assertJsonPath('message', 'Booking request submitted successfully.');

        $booking = Booking::where('service_id', $service->id)->firstOrFail();

        $customerDashboard = $this->getJson('/dashboard/data');
        $customerDashboard->assertOk()
            ->assertJsonPath('role', 'customer')
            ->assertJsonFragment(['service' => 'Emergency Plumbing Visit']);

        $this->postJson('/logout')->assertOk();

        $this->postJson('/login', [
            'email' => $providerUser->email,
            'password' => 'password123',
        ])->assertOk();

        $this->postJson('/bookings/' . $booking->id . '/status', ['status' => 'accepted'])->assertOk();
        $this->postJson('/bookings/' . $booking->id . '/status', ['status' => 'completed'])->assertOk();

        $this->assertSame('completed', $booking->fresh()->status);

        $this->postJson('/logout')->assertOk();

        $this->postJson('/login', [
            'email' => $customer->email,
            'password' => 'password123',
        ])->assertOk();

        $completedCustomerDashboard = $this->getJson('/dashboard/data');
        $completedCustomerDashboard->assertOk()
            ->assertJsonPath('bookings.0.can_review', true)
            ->assertJsonPath('bookings.0.has_review', false);

        $this->postJson('/bookings/' . $booking->id . '/reviews', [
            'rating' => 5,
            'comment' => 'Provider ne service bohat achi di.',
        ])->assertOk()
            ->assertJsonPath('message', 'Review submitted successfully.');

        $serviceDetail = $this->getJson('/services/' . $service->slug . '/data');
        $serviceDetail->assertOk()
            ->assertJsonPath('service.provider.name', 'Shahnawaz Ali')
            ->assertJsonPath('auth.can_book', true);

        $reviewedCustomerDashboard = $this->getJson('/dashboard/data');
        $reviewedCustomerDashboard->assertOk()
            ->assertJsonPath('bookings.0.has_review', true)
            ->assertJsonPath('bookings.0.review.rating', 5);
    }
}

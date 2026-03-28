<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaDataEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_endpoint_returns_featured_marketplace_data(): void
    {
        [$service, $providerUser] = $this->createMarketplaceService(
            categoryName: 'Plumbing',
            categorySlug: 'plumbing',
            title: 'Emergency Pipe Repair',
            location: 'Karachi'
        );

        $customer = User::factory()->create(['role' => 'customer']);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'provider_id' => $providerUser->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay(),
            'address' => 'Karachi',
            'notes' => 'Urgent help needed',
            'status' => 'completed',
            'total_amount' => $service->price,
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'provider_id' => $providerUser->id,
            'rating' => 5,
            'comment' => 'Fast and reliable',
        ]);

        $response = $this->getJson('/api/landing');

        $response->assertOk()
            ->assertJsonPath('services.0.slug', $service->slug)
            ->assertJsonPath('services.0.provider.name', $providerUser->name)
            ->assertJsonPath('services.0.provider.rating_avg', 5)
            ->assertJsonPath('providers.0.city', 'Karachi')
            ->assertJsonPath('categories.0.slug', 'plumbing');
    }

    public function test_services_data_endpoint_filters_by_category_and_location(): void
    {
        [$matchingService] = $this->createMarketplaceService(
            categoryName: 'Plumbing',
            categorySlug: 'plumbing',
            title: 'Kitchen Sink Repair',
            location: 'Lahore'
        );

        $this->createMarketplaceService(
            categoryName: 'Electrical',
            categorySlug: 'electrical',
            title: 'Ceiling Fan Wiring',
            location: 'Karachi'
        );

        $response = $this->getJson('/services/data?category=plumbing&location=Lahore&search=Kitchen');

        $response->assertOk()
            ->assertJsonCount(1, 'services')
            ->assertJsonPath('services.0.slug', $matchingService->slug)
            ->assertJsonPath('summary.results', 1)
            ->assertJsonPath('filters.category', 'plumbing')
            ->assertJsonPath('filters.location', 'Lahore');
    }

    /**
     * @return array{0: Service, 1: User}
     */
    protected function createMarketplaceService(
        string $categoryName,
        string $categorySlug,
        string $title,
        string $location
    ): array {
        $category = Category::create([
            'name' => $categoryName,
            'slug' => $categorySlug,
            'description' => $categoryName . ' services',
        ]);

        $providerUser = User::factory()->create([
            'name' => $title . ' Provider',
            'role' => 'provider',
            'city' => $location,
            'address' => $location . ' Center',
            'phone' => '03001234567',
        ]);

        $provider = Provider::create([
            'user_id' => $providerUser->id,
            'bio' => 'Experienced technician',
            'experience_years' => 5,
            'hourly_rate' => 2500,
            'service_area' => $location,
            'availability' => 'Mon-Sat',
            'approved_at' => now(),
            'is_featured' => true,
        ]);

        $service = Service::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString() . '-' . $provider->id,
            'short_description' => $title . ' short description',
            'description' => $title . ' full description',
            'price' => 3500,
            'price_type' => 'fixed',
            'duration_minutes' => 90,
            'is_active' => true,
        ]);

        $service->categories()->attach($category->id);

        return [$service, $providerUser];
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Services\ContentSafety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function __construct(private readonly ContentSafety $contentSafety)
    {
    }

    public function index(Request $request)
    {
        $query = Service::with(['category', 'categories', 'provider.user'])
            ->where('is_active', true)
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'));

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('slug', $request->input('category')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhereHas('provider.user', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('location')) {
            $location = $request->input('location');
            $query->where(function ($inner) use ($location) {
                $inner->whereHas('provider', function ($providerQuery) use ($location) {
                    $providerQuery->where('service_area', 'like', "%{$location}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('city', 'like', "%{$location}%")
                            ->orWhere('address', 'like', "%{$location}%"));
                });
            });
        }

        $services = $query->latest()->get();
        $categories = Category::orderBy('name')->get();
        $location = $request->input('location');

        return view('services.index', [
            'services' => $services,
            'categories' => $categories,
            'locationMapUrl' => $location ? $this->googleMapEmbedUrl($location) : null,
            'locationMapSearchUrl' => $location ? $this->googleMapSearchUrl($location) : null,
            'locationSuggestions' => $this->locationSuggestions(),
        ]);
    }

    public function show(string $slug)
    {
        $service = Service::with(['category', 'categories', 'provider.user', 'bookings.reviews'])
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedCategoryIds = $service->categories->pluck('id')->all();
        if ($relatedCategoryIds === []) {
            $relatedCategoryIds = [$service->category_id];
        }

        $relatedServices = Service::with(['category', 'categories', 'provider.user'])
            ->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $relatedCategoryIds))
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
            ->where('id', '!=', $service->id)
            ->take(3)
            ->get();

        return view('services.show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
            'providerLocationLabel' => $this->providerLocationLabel($service),
            'providerMapUrl' => $this->googleMapEmbedUrl($this->providerLocationLabel($service)),
            'providerMapSearchUrl' => $this->googleMapSearchUrl($this->providerLocationLabel($service)),
        ]);
    }

    public function createProvider()
    {
        $provider = auth()->user()->providerProfile;
        abort_unless($provider && $provider->approved_at, 403, 'Your provider profile must be approved before adding services.');

        return view('services.provider.form', [
            'service' => new Service(),
            'categories' => Category::orderBy('name')->get(),
            'formAction' => route('provider.services.store'),
            'submitLabel' => 'Add service',
            'title' => 'Add New Service',
        ]);
    }

    public function storeProvider(Request $request): RedirectResponse|JsonResponse
    {
        $provider = $request->user()->providerProfile;
        abort_unless($provider && $provider->approved_at, 403, 'Your provider profile must be approved before adding services.');

        [$data, $categoryIds] = $this->validateService($request);

        $data['provider_id'] = $provider->id;
        $data['category_id'] = (int) $categoryIds[0];
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['image_path'] = $this->uploadImage($request);

        $service = Service::create($data);
        $service->categories()->sync($categoryIds);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Service added successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Service added successfully.');
    }

    public function editProvider(Service $service)
    {
        $provider = auth()->user()->providerProfile;
        abort_unless($provider && $service->provider_id === $provider->id, 403);

        return view('services.provider.form', [
            'service' => $service,
            'categories' => Category::orderBy('name')->get(),
            'formAction' => route('provider.services.update', $service),
            'submitLabel' => 'Update service',
            'title' => 'Edit Service',
        ]);
    }

    public function updateProvider(Request $request, Service $service): RedirectResponse|JsonResponse
    {
        $provider = $request->user()->providerProfile;
        abort_unless($provider && $service->provider_id === $provider->id, 403);

        [$data, $categoryIds] = $this->validateService($request);
        $data['category_id'] = (int) $categoryIds[0];
        $data['slug'] = $service->title !== $data['title'] ? $this->uniqueSlug($data['title'], $service->id) : $service->slug;
        $data['image_path'] = $this->uploadImage($request, $service->image_path);

        $service->update($data);
        $service->categories()->sync($categoryIds);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Service updated successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Service updated successfully.');
    }

    public function destroyProvider(Request $request, Service $service): RedirectResponse|JsonResponse
    {
        $provider = $request->user()->providerProfile;
        abort_unless($provider && $service->provider_id === $provider->id, 403);

        $this->deleteImage($service->image_path);
        $service->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Service deleted successfully.',
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Service deleted successfully.');
    }

    public function providerFormData(Request $request, ?Service $service = null): JsonResponse
    {
        $provider = $request->user()->providerProfile;
        abort_unless($provider && $provider->approved_at, 403);
        abort_unless(! $service || $service->provider_id === $provider->id, 403);

        return response()->json([
            'service' => $service ? [
                'id' => $service->id,
                'category_id' => $service->category_id,
                'category_ids' => $service->categories()->pluck('categories.id')->values()->all(),
                'title' => $service->title,
                'short_description' => $service->short_description,
                'description' => $service->description,
                'price' => (float) $service->price,
                'price_type' => $service->price_type,
                'duration_minutes' => $service->duration_minutes,
                'image_path' => $service->image_path ? asset($service->image_path) : null,
                'is_active' => (bool) $service->is_active,
            ] : null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function adminFormData(Request $request, Service $service): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json([
            'service' => [
                'id' => $service->id,
                'provider_id' => $service->provider_id,
                'category_id' => $service->category_id,
                'category_ids' => $service->categories()->pluck('categories.id')->values()->all(),
                'title' => $service->title,
                'short_description' => $service->short_description,
                'description' => $service->description,
                'price' => (float) $service->price,
                'price_type' => $service->price_type,
                'duration_minutes' => $service->duration_minutes,
                'is_active' => (bool) $service->is_active,
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'providers' => Provider::with('user')->orderByDesc('id')->get()->map(fn ($provider) => [
                'id' => $provider->id,
                'name' => $provider->user->name,
            ]),
        ]);
    }

    public function storeCategoryOption(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || ($user->isProvider() && $user->providerProfile?->approved_at)), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $this->contentSafety->ensureCleanText([
            'name' => $validated['name'],
        ]);

        $category = Category::create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
            'description' => null,
            'icon' => null,
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
        ]);
    }

    protected function validateService(Request $request): array
    {
        if ($request->hasFile('image') && ! $request->file('image')->isValid()) {
            throw ValidationException::withMessages([
                'image' => 'Image upload failed: ' . $request->file('image')->getErrorMessage(),
            ]);
        }

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array', 'min:1'],
            'category_ids.*' => ['required', 'distinct', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', Rule::in(['fixed', 'hourly'])],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:10240'],
            'generated_image_svg' => ['nullable', 'string', 'max:30000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $categoryIds = collect($validated['category_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($categoryIds === [] && isset($validated['category_id'])) {
            $categoryIds = [(int) $validated['category_id']];
        }

        if ($categoryIds === []) {
            abort(422, 'At least one category is required.');
        }

        $validated['short_description'] = trim((string) ($validated['short_description'] ?? '')) !== ''
            ? trim((string) $validated['short_description'])
            : Str::limit(trim((string) $validated['description']), 255, '');
        $validated['price'] = (float) ($validated['price'] ?? 0);
        $validated['price_type'] = trim((string) ($validated['price_type'] ?? '')) !== ''
            ? (string) $validated['price_type']
            : 'fixed';
        $validated['duration_minutes'] = (int) ($validated['duration_minutes'] ?? 0);

        $this->contentSafety->ensureCleanText([
            'title' => $validated['title'],
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
        ]);

        unset($validated['category_ids']);
        $validated['is_active'] = $request->boolean('is_active');
        unset($validated['image']);
        unset($validated['generated_image_svg']);

        return [$validated, $categoryIds];
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (Service::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function uploadImage(Request $request, ?string $existingPath = null): ?string
    {
        if (! $request->hasFile('image')) {
            if ($request->filled('generated_image_svg')) {
                return $this->saveGeneratedSvg((string) $request->input('generated_image_svg'), $existingPath);
            }

            return $existingPath;
        }

        $directory = public_path('service-images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $file = $request->file('image');
        $this->contentSafety->inspectImage($file);

        $this->deleteImage($existingPath);
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'service-images/' . $filename;
    }

    protected function saveGeneratedSvg(string $svg, ?string $existingPath = null): ?string
    {
        $directory = public_path('service-images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $svg = trim($svg);
        if ($svg === '' || ! str_starts_with($svg, '<svg')) {
            return $existingPath;
        }

        $this->deleteImage($existingPath);

        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.svg';
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $svg);

        return 'service-images/' . $filename;
    }

    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    protected function providerLocationLabel(Service $service): string
    {
        $parts = array_filter([
            $service->provider->service_area,
            $service->provider->user->city,
            $service->provider->user->address,
        ]);

        return implode(', ', $parts);
    }

    protected function googleMapEmbedUrl(string $location): string
    {
        return 'https://www.google.com/maps?q=' . urlencode($location) . '&output=embed';
    }

    protected function googleMapSearchUrl(string $location): string
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($location);
    }

    protected function locationSuggestions(): array
    {
        return collect()
            ->merge(Provider::query()->whereNotNull('approved_at')->whereNotNull('service_area')->pluck('service_area'))
            ->merge(User::query()
                ->whereHas('providerProfile', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
                ->whereNotNull('city')
                ->pluck('city'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->take(20)
            ->values()
            ->all();
    }
}

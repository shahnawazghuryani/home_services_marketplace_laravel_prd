<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SpaPageController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

if (! function_exists('serveSpaResponse')) {
    function serveSpaResponse()
    {
        if (file_exists(public_path('spa/index.html'))) {
            return response()->file(public_path('spa/index.html'));
        }

        return app(HomeController::class)->index(request());
    }
}

Route::get('/', fn () => serveSpaResponse())->name('home');

Route::get('/spa/{any?}', function () {
    abort_unless(file_exists(public_path('spa/index.html')), 404);

    return response()->file(public_path('spa/index.html'));
})->where('any', '.*');

Route::get('/_setup/run', function () {
    $expectedKey = env('DEPLOY_SETUP_KEY');
    $providedKey = (string) request()->query('key', '');

    abort_unless($expectedKey && hash_equals((string) $expectedKey, $providedKey), 403, 'Invalid setup key.');

    $commands = [
        'key:generate' => ['--force' => true],
        'migrate' => ['--force' => true],
        'db:seed' => ['--class' => 'MarketplaceSeeder', '--force' => true],
        'storage:link' => [],
        'optimize:clear' => [],
        'optimize' => [],
    ];

    $result = [];
    foreach ($commands as $command => $options) {
        try {
            Artisan::call($command, $options);
            $result[] = [
                'command' => $command,
                'status' => 'ok',
                'output' => trim(Artisan::output()),
            ];
        } catch (\Throwable $exception) {
            $result[] = [
                'command' => $command,
                'status' => 'error',
                'output' => $exception->getMessage(),
            ];
        }
    }

    return response()->json([
        'message' => 'Setup route executed. Remove this route after success.',
        'result' => $result,
    ]);
});

Route::get('/services', fn () => serveSpaResponse())->name('services.index');
Route::get('/services/data', [SpaPageController::class, 'servicesIndex'])->name('services.data');
Route::get('/services/{slug}', fn () => serveSpaResponse())->name('services.show');
Route::get('/services/{slug}/data', [SpaPageController::class, 'serviceShow'])->name('services.show.data');
Route::get('/providers/{provider}', fn () => serveSpaResponse())->name('providers.show');
Route::get('/providers/{provider}/data', [SpaPageController::class, 'providerShow'])->name('providers.show.data');
Route::get('/auth/state', [AuthController::class, 'state'])->name('auth.state');

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => serveSpaResponse())->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', fn () => serveSpaResponse())->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn () => serveSpaResponse())->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    Route::middleware('role:customer')->group(function () {
        Route::get('/services/{slug}/book', fn () => serveSpaResponse())->name('bookings.create');
        Route::get('/services/{slug}/book/data', [SpaPageController::class, 'bookingCreate'])->name('bookings.create.data');
        Route::post('/services/{slug}/book', [BookingController::class, 'store'])->name('bookings.store');
        Route::post('/bookings/{booking}/reviews', [BookingController::class, 'storeReview'])->name('bookings.reviews.store');
    });

    Route::middleware('role:provider')->group(function () {
        Route::get('/provider/profile/edit', fn () => serveSpaResponse())->name('provider.profile.edit');
        Route::get('/provider/profile/data', [DashboardController::class, 'providerProfileData'])->name('provider.profile.data');
        Route::put('/provider/profile', [DashboardController::class, 'updateProviderProfile'])->name('provider.profile.update');

        Route::prefix('provider/services')->name('provider.services.')->group(function () {
            Route::get('/create', fn () => serveSpaResponse())->name('create');
            Route::get('/create/data', [ServiceController::class, 'providerFormData'])->name('create.data');
            Route::post('/', [ServiceController::class, 'storeProvider'])->name('store');
            Route::get('/{service}/edit', fn () => serveSpaResponse())->name('edit');
            Route::get('/{service}/edit/data', [ServiceController::class, 'providerFormData'])->name('edit.data');
            Route::put('/{service}', [ServiceController::class, 'updateProvider'])->name('update');
            Route::delete('/{service}', [ServiceController::class, 'destroyProvider'])->name('destroy');
        });
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/providers/{provider}/approve', [DashboardController::class, 'approveProvider'])->name('providers.approve');
        Route::post('/categories', [DashboardController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [DashboardController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [DashboardController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/services/{service}/edit', fn () => serveSpaResponse())->name('services.edit');
        Route::get('/services/{service}/edit/data', [ServiceController::class, 'adminFormData'])->name('services.edit.data');
        Route::put('/services/{service}', [DashboardController::class, 'updateAdminService'])->name('services.update');
        Route::delete('/services/{service}', [DashboardController::class, 'destroyAdminService'])->name('services.destroy');
    });

    Route::post('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
});
   3   4
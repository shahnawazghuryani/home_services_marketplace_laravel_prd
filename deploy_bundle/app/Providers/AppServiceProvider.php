<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $supportedLocales = ['en'];
        $request = app(Request::class);

        if ($request->has('lang') && in_array($request->query('lang'), $supportedLocales, true)) {
            session(['locale' => $request->query('lang')]);
        }

        $locale = session('locale', config('app.locale'));

        if (! in_array($locale, $supportedLocales, true)) {
            $locale = config('app.fallback_locale');
        }

        app()->setLocale($locale);

        view()->share('currentLocale', $locale);
        view()->share('supportedLocales', [
            'en' => 'English',
        ]);
    }
}

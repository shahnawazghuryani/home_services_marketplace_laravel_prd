<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function contact(): View
    {
        return view('pages.contact', ['title' => 'Contact & Help']);
    }

    public function privacy(): View
    {
        return view('pages.privacy', ['title' => 'Privacy Policy']);
    }

    public function terms(): View
    {
        return view('pages.terms', ['title' => 'Terms & Conditions']);
    }

    public function providerOnboarding(): View
    {
        return view('pages.provider-onboarding', ['title' => 'Provider Onboarding SOP']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function setLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['locale' => ['required', 'string', Rule::in(config('app.supported_locales', ['hu', 'en']))]],
            ['locale.in' => __('validation.locale.supported')]
        );

        $locale = $validated['locale'];

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);
        $request->setLocale($locale);

        return back();
    }
}

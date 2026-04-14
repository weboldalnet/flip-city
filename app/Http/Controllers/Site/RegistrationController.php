<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;

class RegistrationController extends SiteExtendedController
{
    public function showRegistrationForm()
    {
        return view('flip-city::site.flip-city.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:flip_city_users',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8|confirmed',
            'terms_accepted' => 'required|accepted',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'terms_accepted' => true,
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        // QR kód generálás és e-mail küldés logika ide jönne (pl. Job-ban)

        return redirect()->route('flip-city.profile')->with('success', 'Sikeres regisztráció!');
    }
}

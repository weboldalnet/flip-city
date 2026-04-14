<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Weboldalnet\FlipCity\Mail\FlipCityMail;
use Weboldalnet\FlipCity\Models\User;
use Weboldalnet\FlipCity\Services\QRCodeService;

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
            'is_active' => false,
            'activation_token' => Str::random(64),
        ]);

        $mailData = [
            'subject'     => 'Regisztráció megerősítése',
            'success_res' => 'Köszönjük a regisztrációt!',
            'desc'        => 'A fiók aktiválásához kérjük kattintson az alábbi gombra:<br><br>' .
                             '<a href="' . route('flip-city.activate', $user->activation_token) . '" style="background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Fiók aktiválása</a>',
        ];

        Mail::to($user->email)->send(new FlipCityMail($user, $mailData));

        return redirect()->route('flip-city.register.show')->with('success', 'Sikeres regisztráció! Kérjük, ellenőrizze e-mail fiókját az aktiváláshoz.');
    }

    public function activate($token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return redirect()->route('flip-city.register.show')->with('error', 'Érvénytelen aktiváló link.');
        }

        $qrToken = $user->qr_code_token ?? Str::uuid()->toString();
        $qrSvg = QRCodeService::generateQRCode($qrToken);

        $user->update([
            'is_active' => true,
            'activation_token' => null,
            'activated_at' => now(),
            'qr_code_token' => $qrToken,
            'qr_code_svg' => $qrSvg,
        ]);

        auth()->login($user);

        return redirect()->route('flip-city.profile')->with('success', 'Fiókja sikeresen aktiválva! Üdvözöljük a Flip-City-ben.');
    }
}

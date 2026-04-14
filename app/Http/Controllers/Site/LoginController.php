<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends SiteExtendedController
{
    public function showLoginForm()
    {
        return view("flip-city::site.flip-city.login");
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            "email" => ["required", "email"],
            "password" => ["required"],
        ]);

        if (Auth::attempt($credentials, $request->boolean("remember"))) {
            $user = Auth::user();
            
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    "email" => "A fiókja még nincs aktiválva. Kérjük, ellenőrizze e-mailjeit az aktiváláshoz.",
                ]);
            }

            if ($user->is_blocked) {
                Auth::logout();
                return back()->withErrors([
                    "email" => "A fiókja le van tiltva.",
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route("flip-city.profile"));
        }

        return back()->withErrors([
            "email" => "A megadott hitelesítési adatok nem egyeznek a rekordjainkkal.",
        ])->onlyInput("email");
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route("flip-city.login.show");
    }
}
